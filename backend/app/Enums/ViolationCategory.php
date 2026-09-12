<?php

namespace App\Enums;

/**
 * What kind of off-platform-contact attempt the moderation filter detected.
 */
enum ViolationCategory: string
{
    case SocialMedia = 'social_media';
    case PhoneNumber = 'phone_number';
    case Email = 'email';
    case ExternalUrl = 'external_url';
    case OffPlatformTransaction = 'off_platform_transaction';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SocialMedia => 'Social Media Contact',
            self::PhoneNumber => 'Phone Number',
            self::Email => 'Email Address',
            self::ExternalUrl => 'External Link',
            self::OffPlatformTransaction => 'Off-Platform Transaction',
            self::Other => 'Other',
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
