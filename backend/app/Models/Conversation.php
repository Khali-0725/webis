<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A 1:1 client↔provider thread.
 *
 * The UNIQUE(client_id, provider_user_id) pair means participants always
 * reuse the same thread.
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'provider_user_id',
        'booking_id',
    ];

    protected function casts(): array
    {
        return [
            'client_unread_count' => 'integer',
            'provider_unread_count' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Whether the given user is a participant of this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->client_id === $userId || $this->provider_user_id === $userId;
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id')->withTrashed();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    /**
     * For the conversation list preview - avoids loading every message just
     * to show the last one.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ChatViolation::class);
    }
}
