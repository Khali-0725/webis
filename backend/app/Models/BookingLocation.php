<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The client's exact service location — physically separated from bookings
 * to enforce the privacy rule (R-26). Must be deliberately eager-loaded
 * behind a policy check.
 */
class BookingLocation extends Model
{
    protected $fillable = [
        'booking_id',
        'barangay_id',
        'address_line',
        'latitude',
        'longitude',
        'landmark_notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
