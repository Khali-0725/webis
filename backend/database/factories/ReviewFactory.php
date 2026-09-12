<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'client_id' => User::factory()->client(),
            'provider_profile_id' => ProviderProfile::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'is_visible' => true,
        ];
    }

    public function withReply(): static
    {
        return $this->state(fn () => [
            'provider_reply' => fake()->paragraph(),
            'replied_at' => now(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
