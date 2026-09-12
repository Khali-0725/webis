<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Provider-specific data, 1:1 with a provider-role user.
 *
 * Rating/booking aggregates are recomputed transactionally when a booking
 * completes or a review is stored — search sorting never runs a live AVG().
 */
class ProviderProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'bio',
        'experience_years',
        'base_barangay_id',
        'latitude',
        'longitude',
        'is_accepting_bookings',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'completed_bookings_count' => 'integer',
            'is_accepting_bookings' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeVerified($query)
    {
        return $query->where('verification_status', VerificationStatus::Approved);
    }

    public function scopeAcceptingBookings($query)
    {
        return $query->where('is_accepting_bookings', true);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Approved;
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function baseBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'base_barangay_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ProviderSkill::class);
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(ProviderVerificationDocument::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(ProviderServiceArea::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(ProviderAvailabilityRule::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(ProviderAvailabilityException::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(ProviderPaymentMethod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
