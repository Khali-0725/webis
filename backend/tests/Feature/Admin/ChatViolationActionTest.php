<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Enums\ViolationAdminStatus;
use App\Models\AuditLog;
use App\Models\ChatViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatViolationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_dismiss_a_violation(): void
    {
        $admin = User::factory()->admin()->create();
        $violation = ChatViolation::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/admin/chat-violations/{$violation->id}/dismiss")
            ->assertOk()
            ->assertJsonPath('data.admin_status', ViolationAdminStatus::Dismissed->value);
    }

    public function test_admin_suspend_action_sets_actioned_and_suspends_the_user(): void
    {
        $admin = User::factory()->admin()->create();
        $offender = User::factory()->client()->create();
        $violation = ChatViolation::factory()->create(['user_id' => $offender->id]);

        $this->actingAs($admin)
            ->postJson("/api/admin/chat-violations/{$violation->id}/suspend", [
                'reason' => 'Repeated confirmed off-platform requests',
                'basis' => 'Messaging conduct policy',
                'days' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.admin_status', ViolationAdminStatus::Actioned->value);

        $this->assertEquals(UserStatus::Suspended, $offender->fresh()->status);
        $this->assertNotNull($offender->fresh()->suspended_until);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'auditable_id' => $offender->id]);
        $this->actingAs($admin)->postJson("/api/admin/chat-violations/{$violation->id}/suspend", [
            'reason' => 'Repeated confirmed off-platform requests',
            'basis' => 'Messaging conduct policy',
            'days' => 3,
        ])->assertStatus(409);
        $this->assertSame(1, AuditLog::where('action', 'user.suspended')->where('auditable_id', $offender->id)->count());
    }

    public function test_chat_case_cannot_bypass_suspension_policy(): void
    {
        $admin = User::factory()->admin()->create();
        $offender = User::factory()->client()->create();
        $violation = ChatViolation::factory()->create(['user_id' => $offender->id]);

        $this->actingAs($admin)->postJson("/api/admin/chat-violations/{$violation->id}/suspend")
            ->assertUnprocessable();
        $this->actingAs($admin)->postJson("/api/admin/chat-violations/{$violation->id}/suspend", [
            'reason' => 'Repeated confirmed off-platform requests',
            'basis' => 'Messaging conduct policy',
            'days' => 16,
        ])->assertUnprocessable();

        $this->assertEquals(UserStatus::Active, $offender->fresh()->status);
        $this->assertEquals(ViolationAdminStatus::Open, $violation->fresh()->admin_status);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $client = User::factory()->client()->create();
        $violation = ChatViolation::factory()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/chat-violations')
            ->assertForbidden();
    }

    public function test_viewing_a_violation_writes_an_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $violation = ChatViolation::factory()->create();

        $this->actingAs($admin)
            ->getJson("/api/admin/chat-violations/{$violation->id}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'chat_violation.viewed',
            'auditable_id' => $violation->id,
        ]);
    }
}
