<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_suspend_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$user->id}/suspend")
            ->assertOk();

        $this->assertEquals(UserStatus::Suspended, $user->fresh()->status);
    }

    public function test_suspending_a_user_writes_an_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.suspended',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_admin_can_filter_users_by_role_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->client()->create(['first_name' => 'Findme']);
        User::factory()->provider()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users?role=client&search=Findme')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_is_forbidden_from_listing_users(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }
}
