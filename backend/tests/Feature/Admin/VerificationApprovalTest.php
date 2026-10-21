<?php

namespace Tests\Feature\Admin;

use App\Enums\VerificationDocumentType;
use App\Enums\VerificationStatus;
use App\Enums\DocumentStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderVerificationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class VerificationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocument(int $providerProfileId, VerificationDocumentType $type = VerificationDocumentType::GovernmentId): ProviderVerificationDocument
    {
        return ProviderVerificationDocument::create([
            'provider_profile_id' => $providerProfileId,
            'document_type' => $type,
            'file_path' => 'documents/fake.pdf',
            'original_name' => 'fake.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);
    }

    public function test_one_approved_document_does_not_verify_the_provider(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $document = $this->makeDocument($profile->id);

        $this->actingAs($admin)
            ->patchJson("/api/admin/verification/documents/{$document->id}/approve")
            ->assertOk();

        $profile->refresh();
        $this->assertEquals(DocumentStatus::Approved, $document->fresh()->status);
        $this->assertEquals(VerificationStatus::Pending, $profile->verification_status);
        $this->actingAs($admin)
            ->patchJson("/api/admin/verification/providers/{$profile->id}/approve")
            ->assertUnprocessable();
    }

    public function test_pending_provider_cannot_use_shared_business_routes(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $this->actingAs($provider)->getJson('/api/provider/verification')->assertOk();
        $this->actingAs($provider)->getJson('/api/bookings')->assertForbidden();
        $this->actingAs($provider)->getJson('/api/payments')->assertForbidden();
    }

    public function test_both_required_documents_and_final_decision_verify_provider(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $id = $this->makeDocument($profile->id);
        $permit = $this->makeDocument($profile->id, VerificationDocumentType::BusinessPermit);

        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$id->id}/approve")->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$permit->id}/approve")->assertOk();
        $this->assertEquals(VerificationStatus::Pending, $profile->fresh()->verification_status);

        $this->actingAs($admin)->patchJson("/api/admin/verification/providers/{$profile->id}/approve")->assertOk();
        $this->assertEquals(VerificationStatus::Approved, $profile->fresh()->verification_status);
        $this->assertEquals($admin->id, $profile->fresh()->verified_by);
    }

    public function test_old_approved_document_does_not_override_new_pending_replacement(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $oldId = $this->makeDocument($profile->id);
        $oldId->forceFill(['status' => DocumentStatus::Approved])->save();
        $permit = $this->makeDocument($profile->id, VerificationDocumentType::BusinessPermit);
        $permit->forceFill(['status' => DocumentStatus::Approved])->save();
        $replacement = $this->makeDocument($profile->id);

        $this->actingAs($admin)->patchJson("/api/admin/verification/providers/{$profile->id}/approve")
            ->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$replacement->id}/approve")
            ->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/verification/providers/{$profile->id}/approve")
            ->assertOk();
    }

    public function test_admin_verification_list_includes_pending_providers_without_documents(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->create();

        $this->actingAs($client)->getJson('/api/admin/verification/providers?status=pending')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/verification/providers?status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $profile->id)
            ->assertJsonPath('data.0.required_documents.government_id', 'missing')
            ->assertJsonPath('data.0.required_documents.business_permit', 'missing');
    }

    public function test_rejection_requires_reason_and_non_admin_cannot_decide(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->create();
        $document = $this->makeDocument($profile->id);

        $this->actingAs($client)->patchJson("/api/admin/verification/documents/{$document->id}/approve")->assertForbidden();
        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$document->id}/reject")->assertUnprocessable();
    }

    public function test_private_document_can_only_be_opened_by_owner_or_admin(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/fake.pdf', '%PDF-test');
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $owner->id]);
        $stranger = User::factory()->client()->create();
        $document = $this->makeDocument($profile->id);

        $this->actingAs($stranger)->get("/api/files/verification-document/{$document->id}")->assertForbidden();
        $this->actingAs($owner)->get("/api/files/verification-document/{$document->id}")->assertOk();
        $this->actingAs($admin)->get("/api/files/verification-document/{$document->id}")->assertOk();
    }

    public function test_bulk_action_skips_ineligible_documents(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $pending = $this->makeDocument($profile->id);
        $alreadyApproved = $this->makeDocument($profile->id, VerificationDocumentType::BusinessPermit);
        $alreadyApproved->forceFill(['status' => DocumentStatus::Approved])->save();

        $this->actingAs($admin)->postJson('/api/admin/verification/documents/bulk', [
            'document_ids' => [$pending->id, $alreadyApproved->id], 'action' => 'approve',
        ])->assertOk()->assertJsonPath('data.processed', 1)->assertJsonPath('data.skipped', 1);
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

    public function test_rejecting_optional_document_does_not_revoke_provider_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $optional = $this->makeDocument($profile->id, VerificationDocumentType::SkillCertificate);

        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$optional->id}/reject", [
            'reason' => 'Certificate copy is unreadable.',
        ])->assertOk();

        $this->assertEquals(DocumentStatus::Rejected, $optional->fresh()->status);
        $this->assertEquals(VerificationStatus::Approved, $profile->fresh()->verification_status);
    }

    public function test_rejecting_older_required_document_does_not_override_newer_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $old = $this->makeDocument($profile->id);
        $new = $this->makeDocument($profile->id);
        $new->forceFill(['status' => DocumentStatus::Approved])->save();

        $this->actingAs($admin)->patchJson("/api/admin/verification/documents/{$old->id}/reject", [
            'reason' => 'Old copy is unreadable.',
        ])->assertOk();

        $this->assertEquals(VerificationStatus::Approved, $profile->fresh()->verification_status);
    }

    public function test_bulk_reject_of_optional_document_does_not_revoke_provider_approval(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $optional = $this->makeDocument($profile->id, VerificationDocumentType::SkillCertificate);

        $this->actingAs($admin)->postJson('/api/admin/verification/documents/bulk', [
            'document_ids' => [$optional->id],
            'action' => 'reject',
            'reason' => 'Certificate copy is unreadable.',
        ])->assertOk()->assertJsonPath('data.processed', 1);

        $this->assertEquals(VerificationStatus::Approved, $profile->fresh()->verification_status);
    }
}
