<?php

namespace App\Enums;

/**
 * Identity-verification state of a provider profile, decided by an
 * administrator.
 *
 * Only APPROVED providers may publish services and appear in search -
 * enforced server-side, never by hiding buttons.
 */
enum VerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether this provider may publish and receive bookings.
     */
    public function canAcceptBookings(): bool
    {
        return $this === self::Approved;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
