<?php

namespace App\Enums;

/**
 * What the moderation filter did with a suspicious message.
 */
enum ViolationAction: string
{
    case Warned = 'warned';
    case Blocked = 'blocked';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Warned => 'User Warned',
            self::Blocked => 'Message Blocked',
            self::Flagged => 'Sent & Flagged',
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
