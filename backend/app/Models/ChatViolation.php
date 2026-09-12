<?php

namespace App\Models;

use App\Enums\ViolationAction;
use App\Enums\ViolationAdminStatus;
use App\Enums\ViolationCategory;
use App\Enums\ViolationSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Moderation record for every suspicious chat attempt.
 *
 * An audit table: user/conversation/message references are nullable with
 * nullOnDelete so the record survives even if the offending user or thread
 * is purged.
 */
class ChatViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'message_id',
        'attempted_body',
        'category',
        'matched_rule',
        'severity',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'category' => ViolationCategory::class,
            'severity' => ViolationSeverity::class,
            'action_taken' => ViolationAction::class,
            'admin_status' => ViolationAdminStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeOpen($query)
    {
        return $query->where('admin_status', ViolationAdminStatus::Open);
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
