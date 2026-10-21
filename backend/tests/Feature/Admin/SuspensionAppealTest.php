<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\SuspensionAppeal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspensionAppealTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_can_submit_one_appeal_and_check_status_without_full_access(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create(['password' => 'Secret123!']);
        $this->actingAs($admin)->patchJson("/api/admin/users/{$client->id}/suspend", [
            'basis' => 'Reported incident', 'reason' => 'Pending investigation of a report.', 'days' => 7,
        ])->assertOk();

        $payload = ['email' => $client->email, 'password' => 'Secret123!', 'explanation' => 'Please reconsider this decision based on the attached records.'];
        $this->postJson('/api/appeals', $payload)->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->postJson('/api/appeals', $payload)->assertStatus(409);
        $this->postJson('/api/appeals/status', ['email' => $client->email, 'password' => 'Secret123!'])
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['explanation' => $payload['explanation']]);
        $this->actingAs($client)->getJson('/api/bookings')->assertUnauthorized();
    }

    public function test_admin_decision_is_audited_and_only_current_suspension_is_restored(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create(['password' => 'Secret123!']);
        $this->actingAs($admin)->patchJson("/api/admin/users/{$client->id}/suspend", [
            'basis' => 'Reported incident', 'reason' => 'Pending investigation of a report.', 'days' => 7,
        ])->assertOk();
        $appeal = SuspensionAppeal::create([
            'user_id' => $client->id, 'suspended_at' => $client->fresh()->suspended_at,
            'explanation' => 'Please review this action based on the evidence.',
        ]);

        $this->actingAs($client)->patchJson("/api/admin/appeals/{$appeal->id}", [
            'decision' => 'approved', 'rationale' => 'The evidence supports restoring access.',
        ])->assertUnauthorized();
        $this->actingAs($admin)->patchJson("/api/admin/appeals/{$appeal->id}", [
            'decision' => 'approved', 'rationale' => 'The evidence supports restoring access.',
        ])->assertOk();
        $this->assertSame(UserStatus::Active, $client->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'suspension.appeal_decided', 'auditable_id' => $appeal->id]);
    }
}
