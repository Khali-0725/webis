<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function providerWithProfile(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    private function verifiedProviderWithProfile(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_verified_provider_can_create_and_update_their_own_service(): void
    {
        [$user, $profile] = $this->verifiedProviderWithProfile();
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/provider/services', [
            'service_category_id' => $category->id,
            'title' => 'Emergency pipe repair',
            'description' => str_repeat('Reliable plumbing service. ', 3),
            'pricing_type' => 'fixed',
            'price' => 500,
        ])->assertStatus(201);

        $serviceId = $response->json('data.id');

        $this->actingAs($user)
            ->patchJson("/api/provider/services/{$serviceId}", ['title' => 'Updated title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title');
    }

    public function test_unverified_provider_cannot_create_a_service(): void
    {
        [$user] = $this->providerWithProfile();
        $category = ServiceCategory::factory()->create();

        $this->actingAs($user)->postJson('/api/provider/services', [
            'service_category_id' => $category->id,
            'title' => 'Emergency pipe repair',
            'description' => str_repeat('Reliable plumbing service. ', 3),
            'pricing_type' => 'fixed',
            'price' => 500,
        ])->assertStatus(403);

        $this->assertDatabaseCount('services', 0);
    }

    public function test_provider_cannot_update_another_providers_service(): void
    {
        [$owner, $ownerProfile] = $this->providerWithProfile();
        [$intruder] = $this->providerWithProfile();
        $service = Service::factory()->create(['provider_profile_id' => $ownerProfile->id]);

        $this->actingAs($intruder)
            ->patchJson("/api/provider/services/{$service->id}", ['title' => 'Hijacked'])
            ->assertStatus(403);
    }

    public function test_service_cannot_be_published_until_provider_is_verified(): void
    {
        [$user, $profile] = $this->providerWithProfile();
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($user)
            ->patchJson("/api/provider/services/{$service->id}/publish")
            ->assertStatus(403);

        $this->assertNull($service->fresh()->published_at);
    }

    public function test_verified_provider_can_publish_and_deactivate_their_service(): void
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($user)
            ->patchJson("/api/provider/services/{$service->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $this->actingAs($user)
            ->patchJson("/api/provider/services/{$service->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
