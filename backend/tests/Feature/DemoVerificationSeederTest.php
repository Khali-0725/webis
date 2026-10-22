<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\BarangaySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\ServiceCategorySeeder;
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

    public function test_demo_provider_services_appear_in_the_local_public_catalog(): void
    {
        Storage::fake(config('webis.uploads.disk'));
        $this->seed(BarangaySeeder::class);
        $this->seed(ServiceCategorySeeder::class);
        $this->seed(DemoAccountSeeder::class);

        $profile = User::where('email', 'provider@webis.test')->firstOrFail()->providerProfile;

        $this->assertEqualsCanonicalizing(
            ['Pipe Repair', 'Outlet Install', 'General Cleaning'],
            $profile->services()->pluck('title')->all(),
        );
        $this->assertSame([
            'General Cleaning' => ['House Cleaning', '1200.00', 180],
            'Outlet Install' => ['Electrical', '600.00', 45],
            'Pipe Repair' => ['Plumbing', '500.00', 60],
        ], $profile->services()->with('category')->get()
            ->mapWithKeys(fn (Service $service) => [
                $service->title => [$service->category->name, $service->price, $service->duration_minutes],
            ])->sortKeys()->all());
        $this->getJson('/api/services')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_reseeding_demo_account_does_not_duplicate_its_services(): void
    {
        Storage::fake(config('webis.uploads.disk'));
        $this->seed(BarangaySeeder::class);
        $this->seed(ServiceCategorySeeder::class);
        $this->seed(DemoAccountSeeder::class);
        $this->seed(DemoAccountSeeder::class);

        $profile = User::where('email', 'provider@webis.test')->firstOrFail()->providerProfile;

        $this->assertSame(3, Service::where('provider_profile_id', $profile->id)->count());
    }
}
