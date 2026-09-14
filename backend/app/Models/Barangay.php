<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master list of barangays. Scoped to Tanza, Cavite (41 barangays).
 *
 * Barangays are deactivated, never deleted — users, provider profiles,
 * service areas and booking locations all reference them.
 */
class Barangay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'municipality',
        'province',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class, 'base_barangay_id');
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(ProviderServiceArea::class);
    }

    public function bookingLocations(): HasMany
    {
        return $this->hasMany(BookingLocation::class);
    }
}
