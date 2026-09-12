<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chat messages inside a conversation.
 *
 * Blocked messages are never inserted here — they live only in
 * chat_violations.attempted_body.
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'moderation_status',
    ];

    protected function casts(): array
    {
        return [
            'moderation_status' => ModerationStatus::class,
            'read_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
