<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRecordFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_date_filter_and_trash_order_use_the_relevant_timestamps(): void
    {
        $admin = User::factory()->admin()->create();

        $this->travelTo(now()->setDate(2026, 1, 5));
        $older = User::factory()->client()->create();
        $this->travelTo(now()->setDate(2026, 2, 5));
        $newer = User::factory()->client()->create();

        $this->actingAs($admin)->getJson('/api/admin/users?role=client&start_date=2026-02-01&end_date=2026-02-28')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $newer->id);

        $this->travelTo(now()->setDate(2026, 3, 1));
        $newer->delete();
        $this->travelTo(now()->setDate(2026, 3, 2));
        $older->delete();

        $this->withSession(['webis.last_user_activity' => now()->timestamp])
            ->actingAs($admin)->getJson('/api/admin/users?trashed=only&role=client&sort=newest')
            ->assertOk()->assertJsonPath('data.0.id', $older->id)->assertJsonPath('data.1.id', $newer->id);
        $this->actingAs($admin)->getJson('/api/admin/users?trashed=only&role=client&sort=oldest&start_date=2026-03-01&end_date=2026-03-01')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $newer->id);

        $this->travelBack();
    }

    public function test_invalid_admin_record_dates_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/admin/users?start_date=2026-02-10&end_date=2026-02-01')
            ->assertUnprocessable();
    }
}
