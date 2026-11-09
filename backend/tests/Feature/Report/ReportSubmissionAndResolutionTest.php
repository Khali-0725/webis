<?php

namespace Tests\Feature\Report;

use App\Enums\BookingStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\ProviderViolation;
use App\Models\Report;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionAndResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_resolve_and_closed_report_cannot_return_to_reviewing(): void
    {
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'reportable_type' => User::class, 'reportable_id' => User::factory()->create()->id,
            'reason' => ReportReason::Other, 'details' => 'A case for final review.',
        ]);
        $this->actingAs(User::factory()->superAdmin()->create())
            ->postJson("/api/admin/reports/{$report->id}/resolve", ['status' => 'resolved', 'handling_notes' => 'Final decision with evidence.'])
            ->assertOk()->assertJsonPath('data.status', 'resolved');
        $this->postJson("/api/admin/reports/{$report->id}/resolve", ['status' => 'reviewing', 'handling_notes' => 'An older review action was submitted.'])->assertStatus(409);
        $this->getJson('/api/admin/reports')->assertOk()->assertJsonPath('data.0.status', 'resolved');
        $this->assertSame(ReportStatus::Resolved, $report->fresh()->status);
    }

    public function test_a_user_can_submit_a_report_against_another_user(): void
    {
        $reporter = User::factory()->client()->create();
        $booking = Booking::factory()->create(['client_id' => $reporter->id]);
        $target = $booking->providerProfile->user;

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
        $this->actingAs($reporter)->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.0.data.event', 'report.received');
    }

    public function test_a_user_cannot_report_an_unrelated_private_booking_or_account(): void
    {
        $reporter = User::factory()->client()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingAs($reporter)->postJson('/api/reports', [
            'reportable_type' => 'booking', 'reportable_id' => $otherBooking->id,
            'reason' => ReportReason::Other->value, 'details' => 'A private incident I did not witness.',
        ])->assertUnprocessable()->assertJsonValidationErrors('reportable_id');

        $this->actingAs($reporter)->postJson('/api/reports', [
            'reportable_type' => 'user', 'reportable_id' => $otherBooking->providerProfile->user_id,
            'reason' => ReportReason::Other->value, 'details' => 'An unrelated provider account.',
        ])->assertUnprocessable()->assertJsonValidationErrors('reportable_id');
    }

    public function test_client_can_report_a_public_service_or_provider(): void
    {
        $client = User::factory()->client()->create();
        $provider = ProviderProfile::factory()->verified()->create();
        $otherProvider = ProviderProfile::factory()->verified()->create();
        $service = Service::factory()->published()->create(['provider_profile_id' => $otherProvider->id]);

        foreach ([['provider', $provider->id, ProviderProfile::class], ['service', $service->id, Service::class]] as [$type, $id, $model]) {
            $this->actingAs($client)->postJson('/api/reports', [
                'reportable_type' => $type, 'reportable_id' => $id,
                'reason' => ReportReason::InappropriateContent->value,
                'details' => 'Synthetic public listing report for QA.',
            ])->assertCreated();

            $this->assertDatabaseHas('reports', [
                'reporter_id' => $client->id, 'reportable_type' => $model, 'reportable_id' => $id,
            ]);
        }
    }

    public function test_client_cannot_report_the_same_provider_through_another_route_or_after_withdrawing(): void
    {
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $service = Service::factory()->published()->create(['provider_profile_id' => $profile->id]);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);

        $id = $this->actingAs($client)->postJson('/api/reports', [
            'reportable_type' => 'service', 'reportable_id' => $service->id,
            'reason' => ReportReason::Other->value, 'details' => 'First report about this provider.',
        ])->assertCreated()->json('data.id');

        foreach ([['provider', $profile->id], ['booking', $booking->id], ['user', $profile->user_id]] as [$type, $targetId]) {
            $this->actingAs($client)->postJson('/api/reports', [
                'reportable_type' => $type, 'reportable_id' => $targetId,
                'reason' => ReportReason::Other->value, 'details' => 'Repeated report about the same provider.',
            ])->assertStatus(409);
        }

        $this->actingAs($client)->deleteJson("/api/reports/{$id}")->assertOk();
        $this->actingAs($client)->postJson('/api/reports', [
            'reportable_type' => 'user', 'reportable_id' => $profile->user_id,
            'reason' => ReportReason::Other->value, 'details' => 'Repeated report after withdrawal.',
        ])->assertStatus(409);
        $this->assertSame(1, Report::withTrashed()->where('reporter_id', $client->id)->count());
    }

    public function test_provider_cannot_report_same_client_across_bookings_or_after_withdrawing(): void
    {
        $profile = ProviderProfile::factory()->verified()->create();
        $client = User::factory()->client()->create();
        $first = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $second = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);

        $id = $this->actingAs($profile->user)->postJson('/api/reports', [
            'reportable_type' => 'booking', 'reportable_id' => $first->id,
            'reason' => ReportReason::NonPayment->value, 'details' => 'Client did not pay for the work.',
        ])->assertCreated()->json('data.id');

        $this->actingAs($profile->user)->postJson('/api/reports', [
            'reportable_type' => 'booking', 'reportable_id' => $second->id,
            'reason' => ReportReason::Other->value, 'details' => 'Another complaint about the same client.',
        ])->assertStatus(409);

        $this->actingAs($profile->user)->deleteJson("/api/reports/{$id}")->assertOk();
        $this->actingAs($profile->user)->postJson('/api/reports', [
            'reportable_type' => 'user', 'reportable_id' => $client->id,
            'reason' => ReportReason::Other->value, 'details' => 'Complaint after withdrawing the first one.',
        ])->assertStatus(409);
    }

    public function test_admin_sees_suspension_review_eligibility_only_at_ten_reporters_and_eighty_five_percent(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $reportIds = [];

        for ($i = 0; $i < 20; $i++) {
            $client = User::factory()->client()->create();
            Booking::factory()->completed()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
            if ($i === 0) {
                Booking::factory()->completed()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
            }
            if ($i < 17) {
                $reportIds[] = Report::create([
                    'reporter_id' => $client->id,
                    'target_user_id' => $profile->user_id,
                    'reportable_type' => User::class,
                    'reportable_id' => $profile->user_id,
                    'reason' => ReportReason::Other,
                    'details' => 'A completed client complaint.',
                ])->id;
            }
        }

        $this->actingAs($admin)->getJson("/api/admin/reports/{$reportIds[0]}")
            ->assertOk()
            ->assertJsonPath('data.suspension_review.eligible', true)
            ->assertJsonPath('data.suspension_review.reporting_clients', 17)
            ->assertJsonPath('data.suspension_review.completed_clients', 20);

        Report::findOrFail($reportIds[0])->delete();
        $this->actingAs($admin)->getJson("/api/admin/reports/{$reportIds[1]}")
            ->assertOk()
            ->assertJsonPath('data.suspension_review.eligible', false)
            ->assertJsonPath('data.suspension_review.reporting_clients', 16)
            ->assertJsonPath('data.suspension_review.completed_clients', 20);

        $this->assertSame('active', $profile->user->fresh()->status->value);
    }

    public function test_admin_threshold_excludes_reporters_without_completed_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $firstReport = null;

        for ($i = 0; $i < 10; $i++) {
            $client = User::factory()->client()->create();
            $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
            if ($i < 9) {
                $booking->forceFill(['status' => BookingStatus::Completed])->save();
            }
            $report = Report::create([
                'reporter_id' => $client->id,
                'target_user_id' => $profile->user_id,
                'reportable_type' => Booking::class,
                'reportable_id' => $booking->id,
                'reason' => ReportReason::Other,
                'details' => 'Complaint about a booking.',
            ]);
            $firstReport ??= $report;
        }

        $this->actingAs($admin)->getJson("/api/admin/reports/{$firstReport->id}")
            ->assertOk()
            ->assertJsonPath('data.suspension_review.eligible', false)
            ->assertJsonPath('data.suspension_review.reporting_clients', 9)
            ->assertJsonPath('data.suspension_review.completed_clients', 9);
    }

    public function test_non_public_provider_cannot_be_reported_as_a_public_profile(): void
    {
        $client = User::factory()->client()->create();
        $provider = ProviderProfile::factory()->create();

        $this->actingAs($client)->postJson('/api/reports', [
            'reportable_type' => 'provider', 'reportable_id' => $provider->id,
            'reason' => ReportReason::Other->value, 'details' => 'Synthetic private profile report.',
        ])->assertUnprocessable()->assertJsonValidationErrors('reportable_id');
    }

    public function test_a_report_requires_an_explanation_and_non_payment_is_provider_only(): void
    {
        $booking = Booking::factory()->create();
        $client = $booking->client;

        $this->actingAs($client)->postJson('/api/reports', [
            'reportable_type' => 'booking', 'reportable_id' => $booking->id,
            'reason' => ReportReason::Other->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('details');

        $this->actingAs($client)->postJson('/api/reports', [
            'reportable_type' => 'booking', 'reportable_id' => $booking->id,
            'reason' => ReportReason::NonPayment->value, 'details' => 'Client claimed non payment.',
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');
    }

    public function test_a_provider_can_report_a_client_for_non_payment_on_a_booking(): void
    {
        $booking = Booking::factory()->create();
        $booking->providerProfile->forceFill(['verification_status' => VerificationStatus::Approved])->save();
        $reporter = $booking->providerProfile->user;

        $this->actingAs($reporter)
            ->postJson('/api/reports', [
                'reportable_type' => 'booking',
                'reportable_id' => $booking->id,
                'reason' => ReportReason::NonPayment->value,
                'details' => 'Client never paid after the job was done.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Booking::class,
            'reportable_id' => $booking->id,
            'reason' => ReportReason::NonPayment->value,
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
        $reporter = User::factory()->client()->create();
        $report = Report::create([
            'reporter_id' => $reporter->id,
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

        $this->actingAs($admin)->getJson('/api/admin/reports')->assertOk()->assertJsonPath('data.0.id', $report->id);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'report.resolved',
            'auditable_id' => $report->id,
        ]);
        $this->actingAs($reporter)->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.0.data.event', 'report.status_updated')
            ->assertDontSee('Verified and closed.');
    }

    public function test_resolving_client_report_issues_one_provider_violation_and_deducts_performance(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->verified()->create(['performance_rate' => 100]);
        $report = Report::create([
            'reporter_id' => $client->id,
            'target_user_id' => $profile->user_id,
            'reportable_type' => User::class,
            'reportable_id' => $profile->user_id,
            'reason' => ReportReason::Other,
            'details' => 'A valid provider complaint for admin decision.',
        ]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Resolved->value,
            'handling_notes' => 'Evidence confirmed by administrator.',
        ])->assertOk()->assertJsonPath('data.provider_violation.penalty_points', 5);

        $this->assertDatabaseHas('provider_violations', [
            'report_id' => $report->id,
            'provider_profile_id' => $profile->id,
            'issued_by' => $admin->id,
            'penalty_points' => 5,
        ]);
        $this->assertDatabaseHas('provider_profiles', [
            'id' => $profile->id,
            'performance_rate' => 95,
            'violation_count' => 1,
        ]);
    }

    public function test_dismissed_report_does_not_issue_provider_violation(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create(['performance_rate' => 100]);
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'target_user_id' => $profile->user_id,
            'reportable_type' => User::class,
            'reportable_id' => $profile->user_id,
            'reason' => ReportReason::Other,
            'details' => 'A report dismissed by administrator.',
        ]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Dismissed->value,
            'handling_notes' => 'Evidence did not confirm the complaint.',
        ])->assertOk();

        $this->assertDatabaseCount('provider_violations', 0);
        $this->assertDatabaseHas('provider_profiles', ['id' => $profile->id, 'performance_rate' => 100, 'violation_count' => 0]);
    }

    public function test_resolved_report_cannot_deduct_performance_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->verified()->create(['performance_rate' => 100]);
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'target_user_id' => $profile->user_id,
            'reportable_type' => User::class,
            'reportable_id' => $profile->user_id,
            'reason' => ReportReason::Other,
            'details' => 'A report resolved once only.',
        ]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Resolved->value,
            'handling_notes' => 'Confirmed and resolved.',
        ])->assertOk();

        $this->assertSame(1, ProviderViolation::where('report_id', $report->id)->count());
        $this->assertSame(95, $profile->fresh()->performance_rate);
    }

    public function test_admin_decision_requires_handling_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'reportable_type' => User::class,
            'reportable_id' => User::factory()->create()->id,
            'reason' => ReportReason::Other,
            'details' => 'A synthetic report for review.',
        ]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Dismissed->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('handling_notes');

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => ReportStatus::Open->value]);
    }

    public function test_admin_can_acknowledge_then_close_a_report_only_once(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->client()->create();
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => User::factory()->create()->id,
            'reason' => ReportReason::Other,
            'details' => 'A synthetic report for admin review.',
        ]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Reviewing->value,
            'handling_notes' => 'Evidence is being reviewed.',
        ])->assertOk()->assertJsonPath('data.status', ReportStatus::Reviewing->value);

        $this->assertDatabaseHas('audit_logs', ['action' => 'report.acknowledged', 'auditable_id' => $report->id]);
        $this->actingAs($reporter)->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.0.data.event', 'report.acknowledged')
            ->assertDontSee('Evidence is being reviewed.');

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Resolved->value,
            'handling_notes' => 'The synthetic case was reviewed.',
        ])->assertOk();

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/resolve", [
            'status' => ReportStatus::Dismissed->value,
            'handling_notes' => 'Attempted duplicate decision.',
        ])->assertStatus(409);
    }

    public function test_only_super_admin_can_view_an_archived_report(): void
    {
        $target = User::factory()->provider()->create();
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'target_user_id' => $target->id,
            'reportable_type' => User::class,
            'reportable_id' => $target->id,
            'reason' => ReportReason::Other,
            'details' => 'An archived report for review.',
        ]);
        $report->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson("/api/admin/reports/{$report->id}")->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())
            ->getJson("/api/admin/reports/{$report->id}")->assertOk()
            ->assertJsonPath('data.id', $report->id);
    }
}
