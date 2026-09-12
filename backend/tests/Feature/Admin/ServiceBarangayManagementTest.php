<?php

namespace Tests\Feature\Admin;

use App\Models\Barangay;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBarangayManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_a_service_not_owned_by_them(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/services/{$service->id}/toggle")
            ->assertOk();

        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_admin_can_create_update_and_toggle_a_barangay(): void
    {
        $admin = User::factory()->admin()->create();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/barangays', [
                'name' => 'Test Barangay',
                'municipality' => 'Test City',
                'province' => 'Test Province',
            ])
            ->assertCreated()
            ->json('data');

        $this->actingAs($admin)
            ->patchJson("/api/admin/barangays/{$created['id']}", ['name' => 'Renamed Barangay'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Barangay');

        $this->actingAs($admin)
            ->patchJson("/api/admin/barangays/{$created['id']}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_non_admin_cannot_manage_barangays(): void
    {
        $provider = User::factory()->provider()->create();
        $barangay = Barangay::factory()->create();

        $this->actingAs($provider)
            ->patchJson("/api/admin/barangays/{$barangay->id}/toggle")
            ->assertForbidden();
    }
}
