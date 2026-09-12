<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderProfile;
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
}
