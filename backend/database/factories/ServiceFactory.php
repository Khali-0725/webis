<?php

namespace Database\Factories;

use App\Enums\PricingType;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'service_category_id' => ServiceCategory::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'pricing_type' => PricingType::Fixed,
            'price' => fake()->randomFloat(2, 200, 5000),
            'min_price' => null,
            'max_price' => null,
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120, 180]),
            'is_active' => true,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'published_at' => now(),
        ]);
    }

    public function hourly(): static
    {
        return $this->state(fn () => [
            'pricing_type' => PricingType::Hourly,
            'price' => fake()->randomFloat(2, 150, 800),
        ]);
    }

    public function quoteBased(): static
    {
        return $this->state(fn () => [
            'pricing_type' => PricingType::Quote,
            'price' => null,
            'min_price' => fake()->randomFloat(2, 500, 1000),
            'max_price' => fake()->randomFloat(2, 2000, 8000),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
