<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\SettlementMethod;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderPaymentMethod;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'client_id' => User::factory()->client(),
            'provider_profile_id' => ProviderProfile::factory(),
            'provider_payment_method_id' => null,
            'settlement_method' => SettlementMethod::Online,
            'amount' => fake()->randomFloat(2, 200, 5000),
            'currency' => 'PHP',
            'reference_number' => null,
            'status' => PaymentStatus::Pending,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'settlement_method' => SettlementMethod::Cash,
            'provider_payment_method_id' => null,
        ]);
    }

    public function proofSubmitted(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::ProofSubmitted,
            'reference_number' => 'REF-'.strtoupper(fake()->bothify('??####')),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Verified,
            'reference_number' => 'REF-'.strtoupper(fake()->bothify('??####')),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Rejected,
            'rejection_reason' => 'Proof image is unreadable.',
        ]);
    }
}
