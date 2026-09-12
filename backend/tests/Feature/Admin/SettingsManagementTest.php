<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_settings(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::create(['key' => 'platform.name', 'value' => 'WEBIS']);

        $this->actingAs($admin)
            ->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonFragment(['key' => 'platform.name']);
    }

    public function test_admin_can_update_a_known_setting_and_it_persists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [['key' => 'booking.min_lead_hours', 'value' => 48]],
            ])
            ->assertOk()
            ->assertJsonFragment(['key' => 'booking.min_lead_hours']);

        $this->assertEquals(48, SystemSetting::getValue('booking.min_lead_hours'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }

    public function test_an_unknown_setting_key_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [['key' => 'not.a.real.key', 'value' => 'x']],
            ])
            ->assertStatus(422);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/settings')
            ->assertForbidden();
    }
}
