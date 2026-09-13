<?php

namespace App\Services;

use App\Enums\ViolationAction;
use App\Enums\ViolationCategory;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\ChatViolation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    public function __construct(private readonly ChatModerationService $moderation)
    {
    }

    /**
     * Resolves the *other* participant server-side - the caller never gets
     * to name an arbitrary user id directly.
     *
     * @param  array{provider_profile_id?: int, booking_id?: int}  $data
     */
    public function startOrGetConversation(User $actor, array $data): Conversation
    {
        if (! empty($data['provider_profile_id'])) {
            if (! $actor->isClient()) {
                throw DomainException::forbidden('Only a client can start a conversation from a provider profile.');
            }

            $providerProfile = ProviderProfile::findOrFail($data['provider_profile_id']);

            // ->fresh(): a freshly-inserted model doesn't reflect columns
            // left to their DB default (client_unread_count, etc.) - see the
            // identical Booking::create() trap noted in Phase 5.
            return Conversation::firstOrCreate(
                ['client_id' => $actor->id, 'provider_user_id' => $providerProfile->user_id],
            )->fresh();
        }

        if (! empty($data['booking_id'])) {
            $booking = Booking::with('providerProfile')->findOrFail($data['booking_id']);

            if ($actor->isProvider() && $actor->providerProfile?->id !== $booking->provider_profile_id) {
                throw DomainException::forbidden('You do not own this booking.');
            }

            if ($actor->isClient() && $actor->id !== $booking->client_id) {
                throw DomainException::forbidden('This is not your booking.');
            }

            return Conversation::firstOrCreate(
                ['client_id' => $booking->client_id, 'provider_user_id' => $booking->providerProfile->user_id],
                ['booking_id' => $booking->id],
            )->fresh();
        }

        throw DomainException::unprocessable('A provider or a booking is required to start a conversation.');
    }

    /**
     * @return array{status: 'sent'|'blocked'|'requires_confirmation', message?: Message}
     */
    public function send(Conversation $conversation, User $sender, string $body, bool $confirmOverride = false): array
    {
        $result = $this->moderation->evaluate($body);

        // Deliberately outside the transaction below: throwing from inside a
        // DB::transaction() closure rolls back everything written in it,
        // which would silently undo the very violation row this branch
        // exists to create. A single Eloquent create() is already atomic on
        // its own and needs no wrapping transaction.
        if ($result['tier'] === 'blocked') {
            $this->logViolation($conversation, $sender, $body, $result, ViolationAction::Blocked);

            throw DomainException::unprocessable(
                "This message wasn't sent because it looks like it contains contact information. "
                .'WEBIS keeps bookings and payments on the platform for your protection.',
            );
        }

        $outcome = DB::transaction(function () use ($conversation, $sender, $body, $confirmOverride, $result) {
            $tier = $result['tier'];
            $escalated = $this->isEscalated($sender);

            if ($escalated) {
                $tier = 'flagged';
            }

            if ($tier === 'warned' && ! $confirmOverride) {
                return ['status' => 'requires_confirmation'];
            }

            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => $body,
                'moderation_status' => $tier,
            ]);

            if ($tier === 'warned' || $tier === 'flagged') {
                $this->logViolation(
                    $conversation,
                    $sender,
                    $body,
                    $result,
                    $tier === 'flagged' ? ViolationAction::Flagged : ViolationAction::Warned,
                    $message,
                );
            }

            $this->bumpUnreadForRecipient($conversation, $sender);

            return ['status' => 'sent', 'message' => $message];
        });

        if ($outcome['status'] === 'sent') {
            Realtime::push(
                [$this->otherParticipantId($conversation, $sender)],
                'messages',
                ['conversation_id' => $conversation->id],
            );
        }

        return $outcome;
    }

    public function markRead(Conversation $conversation, User $reader): void
    {
        DB::transaction(function () use ($conversation, $reader) {
            $conversation->messages()
                ->where('sender_id', '!=', $reader->id)
                ->unread()
                ->update(['read_at' => now()]);

            if ($conversation->client_id === $reader->id) {
                $conversation->forceFill(['client_unread_count' => 0])->save();
            } else {
                $conversation->forceFill(['provider_unread_count' => 0])->save();
            }
        });

        // The other side's thread shows read receipts (read_at), so it is the
        // one whose view changed here.
        Realtime::push(
            [$this->otherParticipantId($conversation, $reader)],
            'messages',
            ['conversation_id' => $conversation->id],
        );
    }

    private function otherParticipantId(Conversation $conversation, User $user): int
    {
        return $conversation->client_id === $user->id
            ? $conversation->provider_user_id
            : $conversation->client_id;
    }

    public function unreadCountFor(User $user): int
    {
        return (int) $user->clientConversations()->sum('client_unread_count')
            + (int) $user->providerConversations()->sum('provider_unread_count');
    }

    /**
     * "3+ warnings or 2+ blocks inside 24h escalates the user into the admin
     * queue" (§9.3) - a property of the sender's recent history, not of the
     * current message's own content.
     */
    private function isEscalated(User $sender): bool
    {
        $since = Carbon::now()->subHours((int) config('chat.escalation.window_hours', 24));

        $warnCount = ChatViolation::where('user_id', $sender->id)
            ->where('action_taken', ViolationAction::Warned)
            ->where('created_at', '>=', $since)
            ->count();

        $blockCount = ChatViolation::where('user_id', $sender->id)
            ->where('action_taken', ViolationAction::Blocked)
            ->where('created_at', '>=', $since)
            ->count();

        return $warnCount >= (int) config('chat.escalation.warn_threshold', 3)
            || $blockCount >= (int) config('chat.escalation.block_threshold', 2);
    }

    /**
     * @param  array{tier: string, category: ?ViolationCategory, matched_rule: ?string, severity: mixed}  $result
     */
    private function logViolation(
        Conversation $conversation,
        User $sender,
        string $body,
        array $result,
        ViolationAction $action,
        ?Message $message = null,
    ): void {
        ChatViolation::create([
            'user_id' => $sender->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message?->id,
            'attempted_body' => $body,
            'category' => $result['category'] ?? ViolationCategory::Other,
            'matched_rule' => $result['matched_rule'] ?? 'escalation:24h',
            'severity' => $result['severity'] ?? 'medium',
            'action_taken' => $action,
        ]);
    }

    private function bumpUnreadForRecipient(Conversation $conversation, User $sender): void
    {
        if ($conversation->client_id === $sender->id) {
            $conversation->increment('provider_unread_count');
        } else {
            $conversation->increment('client_unread_count');
        }

        $conversation->forceFill(['last_message_at' => now()])->save();
    }
}
