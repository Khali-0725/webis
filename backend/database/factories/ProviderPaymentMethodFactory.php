<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\ProviderPaymentMethod;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderPaymentMethod>
 */
class ProviderPaymentMethodFactory extends Factory
{
    protected $model = ProviderPaymentMethod::class;

    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'type' => fake()->randomElement(PaymentMethodType::cases()),
            'account_name' => fake()->name(),
            'account_ref_masked' => '**** '.fake()->numerify('####'),
            'qr_image_path' => null,
            'instructions' => fake()->optional(0.5)->sentence(),
            'is_default' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
