<?php

namespace App\Enums;

/**
 * How the client intends to settle a booking's payment, chosen at booking
 * time (§16 extension: cash option). Cash is settled in person - no proof
 * upload, the provider confirms receipt directly. Online keeps the existing
 * manual QR-proof workflow (PaymentMethodType channels).
 */
enum SettlementMethod: string
{
    case Cash = 'cash';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Online => 'Online Payment',
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
