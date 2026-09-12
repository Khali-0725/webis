<?php

namespace Tests\Feature\Report;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionAndResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_submit_a_report_against_another_user(): void
    {
        $reporter = User::factory()->client()->create();
        $target = User::factory()->provider()->create();

        $this->actingAs($reporter)
            ->postJson('/api/reports', [
                'reportable_type' => 'user',
                'reportable_id' => $target->id,
                'reason' => ReportReason::FraudOrScam->value,
                'details' => 'Asked me to pay outside the platform.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $target->id,
        ]);
    }

    public function test_an_invalid_reportable_type_is_rejected(): void
    {
        $reporter = User::factory()->client()->create();

        $this->actingAs($reporter)
            ->postJson('/api/reports', [
                'reportable_type' => 'App\\Models\\SystemSetting',
                'reportable_id' => 1,
                'reason' => ReportReason::Other->value,
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_resolve_a_report(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::create([
            'reporter_id' => User::factory()->create()->id,
            'reportable_type' => User::class,
            'reportable_id' => User::factory()->create()->id,
            'reason' => ReportReason::Other,
            'details' => 'Test report.',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/admin/reports/{$report->id}/resolve", [
                'status' => ReportStatus::Resolved->value,
                'handling_notes' => 'Verified and closed.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Resolved->value);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'report.resolved',
            'auditable_id' => $report->id,
        ]);
    }
}
