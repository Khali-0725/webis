<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::create(['actor_id' => $admin->id, 'action' => 'user.suspended']);
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'verification.approved']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/audit-logs?action=suspend')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_account_history_is_scoped_to_one_user(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'user.suspended',
            'auditable_type' => User::class, 'auditable_id' => $subject->id]);
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'user.suspended',
            'auditable_type' => User::class, 'auditable_id' => $other->id]);

        $response = $this->actingAs($admin)->getJson("/api/admin/audit-logs?user_id={$subject->id}")
            ->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($subject->id, $response->json('data.0.auditable_id'));
    }
}
