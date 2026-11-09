<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User-submitted complaints, polymorphic: about a user, a service, or a
 * booking.
 */
class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reporter_id',
        'target_user_id',
        'target_user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing]);
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function handledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function providerViolation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProviderViolation::class);
    }

    /** Resolve the person behind every report route, from the reporter's perspective. */
    public static function targetUserIdFor(EloquentModel $target, User $reporter): ?int
    {
        if ($target instanceof User) {
            return $target->id;
        }

        if ($target instanceof ProviderProfile) {
            return $target->user_id;
        }

        if ($target instanceof Service) {
            return $target->providerProfile?->user_id;
        }

        if ($target instanceof Booking) {
            if ($target->client_id === $reporter->id) {
                return $target->providerProfile?->user_id;
            }

            if ($target->providerProfile?->user_id === $reporter->id) {
                return $target->client_id;
            }
        }

        return null;
    }
}
