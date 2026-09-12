<?php

namespace Tests\Feature\Provider;

use App\Models\Barangay;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_view_and_update_their_own_profile(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);
        $barangay = Barangay::factory()->create();

        $this->actingAs($user)->getJson('/api/provider/profile')->assertOk();

        $this->actingAs($user)
            ->patchJson('/api/provider/profile', [
                'business_name' => 'Josh Plumbing Services',
                'base_barangay_id' => $barangay->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Josh Plumbing Services');
    }

    public function test_client_cannot_access_provider_profile_routes(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->getJson('/api/provider/profile')->assertStatus(403);
    }

    public function test_provider_can_replace_their_skills(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson('/api/provider/skills', [
            'skills' => ['Pipe fitting', 'Leak repair'],
        ])->assertOk();

        $this->assertCount(2, $response->json('data.skills'));
    }

    public function test_skills_reject_case_insensitive_duplicates(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson('/api/provider/skills', ['skills' => ['Pipe fitting', 'pipe fitting']])
            ->assertStatus(422);
    }

    public function test_provider_can_replace_their_service_areas(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);
        $barangays = Barangay::factory()->count(2)->create();

        $this->actingAs($user)
            ->putJson('/api/provider/service-areas', ['barangay_ids' => $barangays->pluck('id')->all()])
            ->assertOk()
            ->assertJsonCount(2, 'data.service_areas');
    }
}
