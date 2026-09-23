<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single past-work-history entry on a provider's profile, distinct from
 * their currently published services and their `experience_years` count.
 */
class ProviderWorkExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id',
        'role_title',
        'employer_name',
        'description',
        'started_on',
        'ended_on',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function isCurrent(): bool
    {
        return $this->ended_on === null;
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
