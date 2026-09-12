<?php

namespace Tests\Feature\Public;

use App\Models\Barangay;
use App\Models\ProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_providers_are_listed(): void
    {
        ProviderProfile::factory()->verified()->create(['business_name' => 'Verified Plumbing']);
        ProviderProfile::factory()->create(['business_name' => 'Pending Plumbing']);

        $response = $this->getJson('/api/providers')->assertOk();

        $names = collect($response->json('data'))->pluck('business_name');

        $this->assertTrue($names->contains('Verified Plumbing'));
        $this->assertFalse($names->contains('Pending Plumbing'));
    }

    public function test_search_matches_business_name(): void
    {
        ProviderProfile::factory()->verified()->create(['business_name' => 'Ace Electricians']);
        ProviderProfile::factory()->verified()->create(['business_name' => 'Zen Cleaning']);

        $response = $this->getJson('/api/providers?search=Ace')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_by_barangay(): void
    {
        $barangay = Barangay::factory()->create();
        ProviderProfile::factory()->verified()->create(['base_barangay_id' => $barangay->id]);
        ProviderProfile::factory()->verified()->create();

        $response = $this->getJson("/api/providers?barangay_id={$barangay->id}")->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
