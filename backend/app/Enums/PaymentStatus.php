<?php

namespace App\Enums;

/**
 * Lifecycle of a booking payment.
 *
 * WEBIS uses a manual QR Ph workflow: the client scans the provider's QR
 * code, pays through their banking app, and uploads proof. Nothing here
 * pretends money moved - VERIFIED means a human (provider or admin) looked
 * at the proof and confirmed it.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case ProofSubmitted = 'proof_submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Payment',
            self::ProofSubmitted => 'Proof Submitted',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Statuses that need someone's attention, in the order dashboards
     * surface them.
     *
     * @return array<int, self>
     */
    public static function awaitingReview(): array
    {
        return [self::ProofSubmitted];
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Verified, self::Refunded], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
