<?php

namespace Tests\Feature\Admin;

use App\Enums\VerificationDocumentType;
use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderVerificationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocument(int $providerProfileId): ProviderVerificationDocument
    {
        return ProviderVerificationDocument::create([
            'provider_profile_id' => $providerProfileId,
            'document_type' => VerificationDocumentType::GovernmentId,
            'file_path' => 'documents/fake.pdf',
            'original_name' => 'fake.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);
    }

    public function test_approving_a_document_verifies_the_provider_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $document = $this->makeDocument($profile->id);

        $this->actingAs($admin)
            ->patchJson("/api/admin/verification/documents/{$document->id}/approve")
            ->assertOk();

        $profile->refresh();
        $this->assertEquals(VerificationStatus::Approved, $profile->verification_status);
        $this->assertNotNull($profile->verified_at);
        $this->assertEquals($admin->id, $profile->verified_by);
    }

    public function test_rejecting_a_document_records_the_rejection_reason_on_the_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $document = $this->makeDocument($profile->id);

        $this->actingAs($admin)
            ->patchJson("/api/admin/verification/documents/{$document->id}/reject", ['reason' => 'Blurry ID photo.'])
            ->assertOk();

        $profile->refresh();
        $this->assertEquals(VerificationStatus::Rejected, $profile->verification_status);
        $this->assertEquals('Blurry ID photo.', $profile->rejection_reason);
    }
}
