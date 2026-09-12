<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderAvailabilityException;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function providerWithProfile(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_provider_can_replace_their_weekly_rules(): void
    {
        [$user] = $this->providerWithProfile();

        $this->actingAs($user)
            ->putJson('/api/provider/availability/rules', [
                'rules' => [
                    ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_minutes' => 60],
                    ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '15:00', 'slot_minutes' => 30],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('provider_availability_rules', 2);
    }

    public function test_rule_end_time_must_be_after_start_time(): void
    {
        [$user] = $this->providerWithProfile();

        $this->actingAs($user)
            ->putJson('/api/provider/availability/rules', [
                'rules' => [
                    ['day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '08:00', 'slot_minutes' => 60],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_provider_can_create_and_delete_an_exception(): void
    {
        [$user] = $this->providerWithProfile();

        $response = $this->actingAs($user)
            ->postJson('/api/provider/availability/exceptions', [
                'date' => now()->addDays(5)->toDateString(),
                'is_closed' => true,
                'reason' => 'Holiday',
            ])
            ->assertStatus(201);

        $exceptionId = $response->json('data.id');

        $this->actingAs($user)
            ->deleteJson("/api/provider/availability/exceptions/{$exceptionId}")
            ->assertOk();

        $this->assertDatabaseMissing('provider_availability_exceptions', ['id' => $exceptionId]);
    }

    public function test_provider_cannot_delete_another_providers_exception(): void
    {
        [, $ownerProfile] = $this->providerWithProfile();
        [$intruder] = $this->providerWithProfile();

        $exception = ProviderAvailabilityException::create([
            'provider_profile_id' => $ownerProfile->id,
            'date' => now()->addDays(5)->toDateString(),
            'is_closed' => true,
        ]);

        $this->actingAs($intruder)
            ->deleteJson("/api/provider/availability/exceptions/{$exception->id}")
            ->assertStatus(403);
    }
}
