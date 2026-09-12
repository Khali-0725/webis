<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_match_seeded_data(): void
    {
        $admin = User::factory()->admin()->create();
        Booking::factory()->count(3)->create();
        Booking::factory()->completed()->count(2)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/analytics/summary')
            ->assertOk();

        $this->assertEquals(5, $response->json('data.bookings.total'));
        $this->assertEquals(2, $response->json('data.bookings.completed'));
    }

    public function test_summary_reports_total_earnings_from_verified_payments_only(): void
    {
        $admin = User::factory()->admin()->create();
        Payment::factory()->verified()->create(['amount' => 1000]);
        Payment::factory()->verified()->create(['amount' => 500]);
        Payment::factory()->create(['amount' => 9999]); // pending - must not count

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/analytics/summary')
            ->assertOk();

        $this->assertEquals(1500, $response->json('data.payments.total_earnings'));
    }

    public function test_earnings_over_time_groups_verified_payments_by_day(): void
    {
        $admin = User::factory()->admin()->create();
        Payment::factory()->verified()->create(['amount' => 300]);
        Payment::factory()->verified()->create(['amount' => 200]);
        Payment::factory()->create(['amount' => 9999]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/analytics/earnings-over-time')
            ->assertOk();

        $rows = $response->json('data');
        $this->assertCount(1, $rows);
        $this->assertEquals(500, $rows[0]['total']);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/admin/analytics/summary')
            ->assertForbidden();
    }
}
