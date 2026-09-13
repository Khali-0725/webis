<?php

namespace App\Enums;

/**
 * Why a user filed a report against another user, a service, or a booking.
 */
enum ReportReason: string
{
    case OffPlatformTransaction = 'off_platform_transaction';
    case FraudOrScam = 'fraud_or_scam';
    case InappropriateContent = 'inappropriate_content';
    case PoorServiceQuality = 'poor_service_quality';
    case Impersonation = 'impersonation';
    case NonPayment = 'non_payment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OffPlatformTransaction => 'Asked to transact off-platform',
            self::FraudOrScam => 'Fraud or scam',
            self::InappropriateContent => 'Inappropriate content or behaviour',
            self::PoorServiceQuality => 'Poor service quality',
            self::Impersonation => 'Impersonation / fake account',
            self::NonPayment => 'Client did not pay',
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
