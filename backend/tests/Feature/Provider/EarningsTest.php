<?php

namespace Tests\Feature\Provider;

use App\Models\Payment;
use App\Models\ProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_sees_only_their_own_verified_earnings(): void
    {
        $mine = ProviderProfile::factory()->create();
        $theirs = ProviderProfile::factory()->create();

        Payment::factory()->verified()->create(['provider_profile_id' => $mine->id, 'amount' => 1000]);
        Payment::factory()->verified()->create(['provider_profile_id' => $theirs->id, 'amount' => 9999]);
        Payment::factory()->create(['provider_profile_id' => $mine->id, 'amount' => 500]); // pending, must not count

        $response = $this->actingAs($mine->user)
            ->getJson('/api/provider/earnings/summary')
            ->assertOk();

        $this->assertEquals(1000, $response->json('data.total_earnings'));
        $this->assertEquals(1, $response->json('data.verified_count'));
    }

    public function test_earnings_over_time_is_scoped_to_the_caller(): void
    {
        $mine = ProviderProfile::factory()->create();
        $theirs = ProviderProfile::factory()->create();

        Payment::factory()->verified()->create(['provider_profile_id' => $mine->id, 'amount' => 300]);
        Payment::factory()->verified()->create(['provider_profile_id' => $theirs->id, 'amount' => 5000]);

        $response = $this->actingAs($mine->user)
            ->getJson('/api/provider/earnings/over-time')
            ->assertOk();

        $rows = $response->json('data');
        $this->assertCount(1, $rows);
        $this->assertEquals(300, $rows[0]['total']);
    }

    public function test_a_client_is_forbidden(): void
    {
        $client = \App\Models\User::factory()->client()->create();

        $this->actingAs($client)
            ->getJson('/api/provider/earnings/summary')
            ->assertForbidden();
    }
}
