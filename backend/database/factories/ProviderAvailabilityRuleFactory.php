<?php

namespace Database\Factories;

use App\Models\ProviderAvailabilityRule;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderAvailabilityRule>
 */
class ProviderAvailabilityRuleFactory extends Factory
{
    protected $model = ProviderAvailabilityRule::class;

    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'day_of_week' => fake()->numberBetween(1, 5), // Mon-Fri default
            'start_time' => '08:00',
            'end_time' => '17:00',
            'slot_minutes' => 60,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
