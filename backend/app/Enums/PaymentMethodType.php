<?php

namespace App\Enums;

/**
 * QR Ph channels a provider may accept payments through.
 *
 * `qrph` is the interoperable national QR standard (any participating bank
 * or e-wallet can scan it). GCash / Maya / bank details are kept as distinct
 * types because providers commonly display a purpose-built code alongside.
 */
enum PaymentMethodType: string
{
    case QrPh = 'qrph';
    case Gcash = 'gcash';
    case Maya = 'maya';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::QrPh => 'QR Ph',
            self::Gcash => 'GCash',
            self::Maya => 'Maya',
            self::Bank => 'Bank Transfer',
        };
    }

    /**
     * Whether a QR image is expected for this channel.
     */
    public function usesQrImage(): bool
    {
        return $this !== self::Bank;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
