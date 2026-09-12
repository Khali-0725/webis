<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Runtime configuration the admin can change (maintenance mode, moderation
 * thresholds, booking lead times). Values are JSON.
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Get a setting value by key, with a default fallback.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
