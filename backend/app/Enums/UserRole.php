<?php

namespace App\Enums;

/**
 * The three primary WEBIS roles.
 *
 * Roles are mutually exclusive and fixed, so they live in a single column on
 * `users` rather than a roles/role_user pivot. A user can never change their
 * own role - only an administrator can, and that action is audited.
 */
enum UserRole: string
{
    case Client = 'client';
    case Provider = 'provider';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Provider => 'Service Provider',
            self::Admin => 'Administrator',
        };
    }

    /**
     * Roles a visitor is allowed to self-register as.
     *
     * @return array<int, string>
     */
    public static function selfRegisterable(): array
    {
        return [self::Client->value, self::Provider->value];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Where the SPA should land this role after sign-in.
     */
    public function homePath(): string
    {
        return match ($this) {
            self::Client => '/client/dashboard',
            self::Provider => '/provider/dashboard',
            self::Admin => '/admin/dashboard',
        };
    }
}
