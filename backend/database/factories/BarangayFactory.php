<?php

namespace Database\Factories;

use App\Models\Barangay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barangay>
 */
class BarangayFactory extends Factory
{
    protected $model = Barangay::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'municipality' => 'Tanza',
            'province' => 'Cavite',
            'latitude' => fake()->latitude(14.25, 14.40),
            'longitude' => fake()->longitude(120.80, 120.95),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
