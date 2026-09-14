<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The core transaction: a client books a service from a provider.
 *
 * Every important value is derived server-side. The client never posts
 * client_id, price, or status.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'client_id',
        'provider_profile_id',
        'service_id',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'quoted_price',
        'final_price',
        'client_notes',
        'provider_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'scheduled_date' => 'date',
            'quoted_price' => 'decimal:2',
            'final_price' => 'decimal:2',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForProvider($query, int $providerProfileId)
    {
        return $query->where('provider_profile_id', $providerProfileId);
    }

    public function scopeWithStatus($query, BookingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Bookings that occupy a time slot (for conflict prevention).
     */
    public function scopeSlotBlocking($query)
    {
        return $query->whereIn('status', array_map(
            fn (BookingStatus $s) => $s->value,
            BookingStatus::slotBlocking()
        ));
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    // withTrashed() on the three parents: a booking is a historical record
    // and must keep rendering its client, provider and service even after
    // any of them has been soft-deleted from the admin panel.
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('created_at');
    }

    public function location(): HasOne
    {
        return $this->hasOne(BookingLocation::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
