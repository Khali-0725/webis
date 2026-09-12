<?php

namespace App\Enums;

/**
 * Administrator disposition of a chat violation record.
 */
enum ViolationAdminStatus: string
{
    case Open = 'open';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';
    case Actioned = 'actioned';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Reviewed => 'Reviewed',
            self::Dismissed => 'Dismissed',
            self::Actioned => 'Action Taken',
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
