<?php

namespace App\Enums;

/**
 * Server-side moderation verdict for a chat message.
 *
 * BLOCKED messages are never inserted into `messages` - the enum has no
 * `blocked` case on purpose; a blocked attempt lives only in
 * `chat_violations.attempted_body`. WARNED and FLAGGED messages were sent,
 * but carry evidence for admin review.
 */
enum ModerationStatus: string
{
    case Allowed = 'allowed';
    case Warned = 'warned';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Allowed => 'Allowed',
            self::Warned => 'Warned',
            self::Flagged => 'Flagged',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
