<?php

namespace Tests\Feature\Report;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Booking;
use App\Models\Report;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionAndResolutionTest extends TestCase
{
    use RefreshDatabase;

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
        $service = Service::factory()->published()->create(['provider_profile_id' => $provider->id]);

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
        $booking->providerProfile->forceFill(['verification_status' => \App\Enums\VerificationStatus::Approved])->save();
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

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'report.resolved',
            'auditable_id' => $report->id,
        ]);
        $this->actingAs($reporter)->getJson('/api/notifications')->assertOk()
            ->assertJsonPath('data.0.data.event', 'report.status_updated')
            ->assertDontSee('Verified and closed.');
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
}
