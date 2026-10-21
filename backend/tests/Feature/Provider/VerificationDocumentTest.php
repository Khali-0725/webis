<?php

namespace Tests\Feature\Provider;

use App\Enums\DocumentStatus;
use App\Enums\VerificationDocumentType;
use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderVerificationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_upload_verification_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/provider/verification/documents', [
                'document_type' => 'government_id',
                'document' => $file,
            ]);
            
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Document uploaded successfully.');
    }

    public function test_replacing_required_document_reopens_review_and_restricts_business_access(): void
    {
        Storage::fake('local');
        $provider = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $provider->id]);
        foreach ([VerificationDocumentType::GovernmentId, VerificationDocumentType::BusinessPermit] as $type) {
            $document = ProviderVerificationDocument::create([
                'provider_profile_id' => $profile->id,
                'document_type' => $type,
                'file_path' => 'documents/previous.pdf',
                'original_name' => 'previous.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 1024,
            ]);
            $document->forceFill(['status' => DocumentStatus::Approved])->save();
        }

        $this->actingAs($provider)->postJson('/api/provider/verification/documents', [
            'document_type' => VerificationDocumentType::GovernmentId->value,
            'document' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
        ])->assertOk();

        $this->assertEquals(VerificationStatus::Pending, $profile->fresh()->verification_status);
        $this->assertNull($profile->fresh()->verified_at);
        $this->actingAs($provider)->getJson('/api/bookings')->assertForbidden();
    }

    public function test_optional_document_does_not_revoke_approved_status(): void
    {
        Storage::fake('local');
        $provider = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $provider->id]);

        $this->actingAs($provider)->postJson('/api/provider/verification/documents', [
            'document_type' => VerificationDocumentType::SkillCertificate->value,
            'document' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
        ])->assertOk();

        $this->assertEquals(VerificationStatus::Approved, $profile->fresh()->verification_status);
    }
}
