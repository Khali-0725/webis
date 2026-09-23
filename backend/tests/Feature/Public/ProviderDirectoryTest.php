<?php

namespace Tests\Feature\Public;

use App\Models\Barangay;
use App\Models\ProviderProfile;
use App\Models\ProviderWorkExperience;
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

    public function test_age_is_hidden_by_default_and_birthdate_is_never_public(): void
    {
        $provider = ProviderProfile::factory()->verified()->create([
            'birthdate' => now()->subYears(30)->toDateString(),
            'show_age_publicly' => false,
        ]);

        $response = $this->getJson("/api/providers/{$provider->id}")->assertOk();

        $this->assertNull($response->json('data.age'));
        $this->assertArrayNotHasKey('birthdate', $response->json('data'));
    }

    public function test_age_is_shown_when_provider_opts_in(): void
    {
        $provider = ProviderProfile::factory()->verified()->create([
            'birthdate' => now()->subYears(30)->toDateString(),
            'show_age_publicly' => true,
        ]);

        $response = $this->getJson("/api/providers/{$provider->id}")->assertOk();

        $this->assertEquals(30, $response->json('data.age'));
        $this->assertArrayNotHasKey('birthdate', $response->json('data'));
    }

    public function test_work_experience_is_visible_on_the_public_provider_profile(): void
    {
        $provider = ProviderProfile::factory()->verified()->create();
        ProviderWorkExperience::factory()->create([
            'provider_profile_id' => $provider->id,
            'role_title' => 'Electrician',
        ]);

        $response = $this->getJson("/api/providers/{$provider->id}")->assertOk();

        $this->assertCount(1, $response->json('data.work_experiences'));
        $this->assertEquals('Electrician', $response->json('data.work_experiences.0.role_title'));
    }
}
