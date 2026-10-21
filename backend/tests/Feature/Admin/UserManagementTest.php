<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
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
            ->patchJson("/api/admin/users/{$user->id}/suspend", ['basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 7])
            ->assertOk();

        $this->assertEquals(UserStatus::Suspended, $user->fresh()->status);
    }

    public function test_suspending_a_user_writes_an_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend", ['basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 7])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.suspended',
            'auditable_id' => $user->id,
        ]);
        $metadata = AuditLog::where('action', 'user.suspended')->where('auditable_id', $user->id)->firstOrFail()->metadata;
        $this->assertSame('Conduct policy', $metadata['basis']);
        $this->assertArrayHasKey('starts_at', $metadata);
        $this->assertArrayHasKey('ends_at', $metadata);
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

    public function test_suspension_cannot_exceed_fifteen_days_and_expires(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend", [
            'basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 16,
        ])->assertUnprocessable();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend", [
            'basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 1,
        ])->assertOk();

        $user = $user->fresh();
        $user->forceFill(['suspended_until' => now()->subMinute()])->save();
        $this->assertTrue($user->canAccessAccount());
        $this->assertEquals(UserStatus::Active, $user->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspension_expired', 'auditable_id' => $user->id]);
    }

    public function test_deployment_can_set_a_stricter_suspension_limit(): void
    {
        config()->set('webis.suspension.max_days', 3);
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend", [
            'basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 4,
        ])->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/suspend", [
            'basis' => 'Conduct policy', 'reason' => 'Repeated confirmed violations', 'days' => 3,
        ])->assertOk();
    }

    public function test_termination_requires_a_reason_and_preserves_the_user_record(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/terminate")->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/terminate", ['reason' => 'Repeated serious policy violations'])->assertOk();

        $this->assertNotNull($user->fresh()->terminated_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.terminated', 'auditable_id' => $user->id]);
        $this->actingAs($admin)->patchJson("/api/admin/users/{$user->id}/restore-terminated", ['reason' => 'Appeal accepted'])->assertOk();
        $this->assertNull($user->fresh()->terminated_at);
    }

    public function test_non_admin_is_forbidden_from_listing_users(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }
}
