<?php

namespace Database\Factories;

use App\Models\ProviderProfile;
use App\Models\ProviderWorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderWorkExperience>
 */
class ProviderWorkExperienceFactory extends Factory
{
    protected $model = ProviderWorkExperience::class;

    public function definition(): array
    {
        $startedOn = fake()->dateTimeBetween('-10 years', '-2 years');

        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'role_title' => fake()->jobTitle(),
            'employer_name' => fake()->company(),
            'description' => fake()->optional(0.6)->sentence(),
            'started_on' => $startedOn,
            'ended_on' => fake()->dateTimeBetween($startedOn, '-1 year'),
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['ended_on' => null]);
    }
}
