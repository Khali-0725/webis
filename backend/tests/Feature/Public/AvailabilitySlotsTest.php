<?php

namespace Tests\Feature\Public;

use App\Models\Booking;
use App\Models\ProviderAvailabilityException;
use App\Models\ProviderAvailabilityRule;
use App\Models\ProviderProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilitySlotsTest extends TestCase
{
    use RefreshDatabase;

    private function futureDateOnWeekday(int $dayOfWeek): Carbon
    {
        $date = now()->addDays(7);

        while ($date->dayOfWeek !== $dayOfWeek) {
            $date->addDay();
        }

        return $date;
    }

    public function test_free_slots_exclude_an_already_booked_range(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $date = $this->futureDateOnWeekday(2);

        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '11:00',
            'slot_minutes' => 60,
        ]);

        Booking::factory()->create([
            'provider_profile_id' => $profile->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
        ]);

        $response = $this->getJson("/api/providers/{$profile->id}/availability?date={$date->toDateString()}")
            ->assertOk();

        $slots = collect($response->json('data'))->pluck('start_time');

        $this->assertTrue($slots->contains('08:00'));
        $this->assertFalse($slots->contains('09:00'));
        $this->assertTrue($slots->contains('10:00'));
    }

    public function test_a_fully_closed_exception_leaves_no_free_slots(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $date = $this->futureDateOnWeekday(2);

        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        ProviderAvailabilityException::create([
            'provider_profile_id' => $profile->id,
            'date' => $date->toDateString(),
            'is_closed' => true,
            'reason' => 'Day off',
        ]);

        $response = $this->getJson("/api/providers/{$profile->id}/availability?date={$date->toDateString()}")
            ->assertOk();

        $this->assertSame([], $response->json('data'));
    }
}
