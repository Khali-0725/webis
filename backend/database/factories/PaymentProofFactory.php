<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'file_path' => 'payment-proofs/'.fake()->uuid().'.jpg',
            'original_name' => 'proof.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10_000, 500_000),
            'reference_number' => 'REF-'.strtoupper(fake()->bothify('??####')),
            'uploaded_by' => User::factory()->client(),
            'superseded_at' => null,
        ];
    }

    public function superseded(): static
    {
        return $this->state(fn () => ['superseded_at' => now()]);
    }
}
