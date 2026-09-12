<?php

namespace Tests\Feature\Admin;

use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderOversightTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_providers_by_verification_status(): void
    {
        $admin = User::factory()->admin()->create();
        ProviderProfile::factory()->verified()->create();
        ProviderProfile::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/providers?verification_status=approved')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_view_a_provider_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = ProviderProfile::factory()->verified()->create();

        $this->actingAs($admin)
            ->getJson("/api/admin/providers/{$provider->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $provider->id);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/providers')
            ->assertForbidden();
    }
}
