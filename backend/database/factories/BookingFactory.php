<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('+1 day', '+30 days');
        $hour = fake()->numberBetween(8, 16);

        return [
            'booking_code' => 'WB-'.strtoupper(Str::random(6)),
            'client_id' => User::factory()->client(),
            'provider_profile_id' => ProviderProfile::factory(),
            'service_id' => Service::factory(),
            'scheduled_date' => $date->format('Y-m-d'),
            'scheduled_start_time' => sprintf('%02d:00', $hour),
            'scheduled_end_time' => sprintf('%02d:00', $hour + 1),
            'status' => BookingStatus::Pending,
            'quoted_price' => fake()->randomFloat(2, 200, 5000),
            'final_price' => null,
            'client_notes' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::InProgress,
            'accepted_at' => now()->subHour(),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Completed,
            'accepted_at' => now()->subDays(2),
            'started_at' => now()->subDay(),
            'completed_at' => now(),
            'final_price' => fake()->randomFloat(2, 200, 5000),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Rejected,
        ]);
    }
}
