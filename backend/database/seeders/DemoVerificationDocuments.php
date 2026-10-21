<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\VerificationDocumentType;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** Private, visibly invalid image fixtures for approved demo providers only. */
class DemoVerificationDocuments
{
    public static function createFor(ProviderProfile $profile, User $admin): void
    {
        foreach ([VerificationDocumentType::GovernmentId, VerificationDocumentType::BusinessPermit] as $type) {
            if ($profile->verificationDocuments()->where('document_type', $type->value)->exists()) {
                continue;
            }

            $label = $type === VerificationDocumentType::GovernmentId ? 'GOVERNMENT ID' : 'BUSINESS PERMIT';
            $image = imagecreatetruecolor(900, 540);
            $background = imagecolorallocate($image, 244, 245, 247);
            $ink = imagecolorallocate($image, 26, 40, 63);
            $warning = imagecolorallocate($image, 175, 40, 32);
            imagefilledrectangle($image, 0, 0, 899, 539, $background);
            imagestring($image, 5, 270, 145, 'WEBIS TEST DATA', $ink);
            imagestring($image, 5, 240, 240, 'DEMO ONLY - NOT VALID', $warning);
            imagestring($image, 5, 300, 325, $label, $ink);
            ob_start();
            imagepng($image);
            $bytes = ob_get_clean();
            imagedestroy($image);

            $path = "documents/demo/{$profile->id}/{$type->value}.png";
            if (! Storage::disk(config('webis.uploads.disk'))->put($path, $bytes)) {
                throw new \RuntimeException('Could not write the demo verification fixture.');
            }
            $profile->verificationDocuments()->create([
                'document_type' => $type->value,
                'file_path' => $path,
                'original_name' => "DEMO-ONLY-{$type->value}.png",
                'mime_type' => 'image/png',
                'size_bytes' => strlen($bytes),
            ])->forceFill([
                'status' => DocumentStatus::Approved,
                'review_notes' => 'Synthetic fixture for local demonstration only; not a valid credential.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();
        }
    }
}
