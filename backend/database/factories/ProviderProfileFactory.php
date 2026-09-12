<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    protected $model = ProviderProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->provider(),
            'business_name' => fake()->company(),
            'bio' => fake()->paragraph(),
            'experience_years' => fake()->numberBetween(1, 20),
            'base_barangay_id' => Barangay::factory(),
            'latitude' => fake()->latitude(14.25, 14.40),
            'longitude' => fake()->longitude(120.80, 120.95),
            'verification_status' => VerificationStatus::Pending,
            'is_accepting_bookings' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => 'Missing documentation.',
        ]);
    }

    public function withRating(float $avg, int $count): static
    {
        return $this->state(fn () => [
            'rating_avg' => $avg,
            'rating_count' => $count,
        ]);
    }
}
