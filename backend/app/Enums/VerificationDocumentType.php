<?php

namespace App\Enums;

/**
 * Kinds of credential a provider may submit for verification.
 *
 * The thesis scopes verification to basic identity validation - no
 * government-API integration, no biometrics. Each type is a document the
 * provider uploads and an administrator eyeballs.
 */
enum VerificationDocumentType: string
{
    case GovernmentId = 'government_id';
    case BarangayClearance = 'barangay_clearance';
    case NbiClearance = 'nbi_clearance';
    case SkillCertificate = 'skill_certificate';
    case BusinessPermit = 'business_permit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GovernmentId => 'Government-issued ID',
            self::BarangayClearance => 'Barangay Clearance',
            self::NbiClearance => 'NBI Clearance',
            self::SkillCertificate => 'Skill Certificate / TESDA',
            self::BusinessPermit => 'Business Permit',
            self::Other => 'Other Document',
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
