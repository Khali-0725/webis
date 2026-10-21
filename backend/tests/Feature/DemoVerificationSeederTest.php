<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoVerificationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_demo_providers_have_private_required_document_fixtures(): void
    {
        Storage::fake(config('webis.uploads.disk'));
        $this->seed(DatabaseSeeder::class);

        $approved = ProviderProfile::where('verification_status', VerificationStatus::Approved->value)
            ->with('verificationDocuments')->get();
        $this->assertCount(11, $approved);
        $this->assertNotNull($approved->firstWhere('business_name', 'Demo Provider Services')->base_barangay_id);

        foreach ($approved as $profile) {
            $documents = $profile->verificationDocuments;
            $this->assertEqualsCanonicalizing(
                ['government_id', 'business_permit'],
                $documents->pluck('document_type')->map(fn ($type) => $type->value)->all(),
            );
            foreach ($documents as $document) {
                $this->assertSame(DocumentStatus::Approved, $document->status);
                $this->assertStringStartsWith('DEMO-ONLY-', $document->original_name);
                Storage::disk(config('webis.uploads.disk'))->assertExists($document->file_path);
            }
        }
    }
}
