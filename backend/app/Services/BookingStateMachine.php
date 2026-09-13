<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of `bookings.status` (per §7.4 of the requirements audit).
 *
 * Every hop is: lock the row, check the transition is legal on the enum,
 * check the actor is allowed to make *this* hop, write the new state, and
 * append one immutable `booking_status_histories` row - all in one
 * transaction.
 */
class BookingStateMachine
{
    public function transition(Booking $booking, BookingStatus $to, User $actor, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $to, $actor, $reason) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw DomainException::conflict("This booking cannot move from {$from->label()} to {$to->label()}.");
            }

            $this->authorizeTransition($locked, $to, $actor, $reason);

            // The provider must have already confirmed receiving the
            // payment (cash confirmed directly, online confirmed after the
            // client's proof) before a job can be marked done.
            if ($to === BookingStatus::Completed && $locked->payment?->status !== PaymentStatus::Verified) {
                throw DomainException::unprocessable(
                    'Payment must be confirmed as received before this booking can be marked as completed.'
                );
            }

            $attributes = ['status' => $to];

            if ($to === BookingStatus::Accepted) {
                $attributes['accepted_at'] = now();
            } elseif ($to === BookingStatus::InProgress) {
                $attributes['started_at'] = now();
            } elseif ($to === BookingStatus::Completed) {
                $attributes['completed_at'] = now();
                $attributes['final_price'] = $locked->final_price ?? $locked->quoted_price;
            } elseif (in_array($to, [BookingStatus::Cancelled, BookingStatus::Rejected], true)) {
                $attributes['cancelled_at'] = now();
                $attributes['cancelled_by'] = $actor->id;
                $attributes['cancellation_reason'] = $reason;
            }

            $locked->forceFill($attributes)->save();

            if ($to === BookingStatus::Completed) {
                // Phase 8 fix: nothing previously kept this counter live -
                // it sat at whatever DemoDataSeeder backfilled once. It
                // feeds "provider rating statistics" alongside rating_avg.
                ProviderProfile::whereKey($locked->provider_profile_id)->increment('completed_bookings_count');
            }

            BookingStatusHistory::create([
                'booking_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor->id,
                'reason' => $reason,
            ]);

            return $locked->fresh();
        });
    }

    private function authorizeTransition(Booking $booking, BookingStatus $to, User $actor, ?string $reason): void
    {
        $isClient = $actor->id === $booking->client_id;
        $isProvider = $actor->providerProfile?->id === $booking->provider_profile_id;

        $providerOnlyTargets = [
            BookingStatus::Accepted,
            BookingStatus::Rejected,
            BookingStatus::InProgress,
            BookingStatus::Completed,
        ];

        if (in_array($to, $providerOnlyTargets, true) && ! $isProvider) {
            throw DomainException::forbidden('Only the assigned provider can do that.');
        }

        if ($booking->status === BookingStatus::Pending && $to === BookingStatus::Cancelled && ! $isClient) {
            throw DomainException::forbidden('Only the client who made this booking can cancel it while it is pending.');
        }

        if ($booking->status === BookingStatus::Accepted && $to === BookingStatus::Cancelled) {
            if (! $isClient && ! $isProvider) {
                throw DomainException::forbidden('Only the client or the provider can cancel this booking.');
            }

            if (! $reason) {
                throw DomainException::unprocessable(
                    'A cancellation reason is required.',
                    ['reason' => ['A cancellation reason is required.']]
                );
            }
        }

        if ($to === BookingStatus::Disputed && ! $isClient && ! $actor->isAdmin()) {
            throw DomainException::forbidden('Only the client or an administrator can raise a dispute.');
        }
    }
}
