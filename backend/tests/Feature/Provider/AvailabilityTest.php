<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderAvailabilityException;
use App\Models\ProviderProfile;
use App\Models\ProviderAvailabilityRule;
use App\Models\Booking;
use App\Services\SlotGenerator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function providerWithProfile(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);

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

        // Soft-deleted, not removed: the row stays with a deleted_at stamp.
        $this->assertSoftDeleted('provider_availability_exceptions', ['id' => $exceptionId]);
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

    public function test_holiday_exclusion_blocks_new_slots_and_requires_confirmation_for_existing_bookings(): void
    {
        [$provider, $profile] = $this->providerWithProfile();
        ProviderAvailabilityRule::factory()->create(['provider_profile_id' => $profile->id, 'day_of_week' => 5]);
        $slots = app(SlotGenerator::class);
        $this->assertNotEmpty($slots->freeSlots($profile->id, '2027-01-01'));
        Booking::factory()->accepted()->create(['provider_profile_id' => $profile->id, 'scheduled_date' => '2027-01-01']);

        $this->actingAs($provider)->patchJson('/api/provider/holidays', ['exclude_all_holidays' => true])
            ->assertStatus(409);
        $this->actingAs($provider)->patchJson('/api/provider/holidays', [
            'exclude_all_holidays' => true, 'confirm_existing_bookings' => true,
        ])->assertOk();
        $this->assertSame([], $slots->freeSlots($profile->id, '2027-01-01'));
        $this->assertFalse($slots->isWithinAvailability($profile->id, '2027-01-01', '08:00', '09:00'));
        $this->actingAs($provider)->getJson('/api/provider/holidays?year=2027')
            ->assertOk()->assertJsonPath('data.exclude_all_holidays', true)
            ->assertJsonPath('data.dates.0.date', '2027-01-01');
    }
}
