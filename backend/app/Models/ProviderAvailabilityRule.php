<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recurring weekly availability, e.g. "Mon-Fri 08:00-17:00, 60min slots".
 *
 * day_of_week: 0 = Sunday … 6 = Saturday (matches PHP's `w` and Carbon).
 */
class ProviderAvailabilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_minutes' => 'integer',
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

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
