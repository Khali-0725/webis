<?php

namespace App\Enums;

/**
 * Account lifecycle state, controlled by administrators.
 *
 * A suspended user keeps their records (bookings, payments, reviews) but
 * cannot authenticate. We never hard-delete a user who has transacted.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function canSignIn(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
