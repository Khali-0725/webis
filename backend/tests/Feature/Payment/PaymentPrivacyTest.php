<?php

namespace Tests\Feature\Payment;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stranger_cannot_fetch_someone_elses_payment_proof_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/secret.jpg', 'fake-bytes');

        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $payment = Payment::factory()->proofSubmitted()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);
        $proof = PaymentProof::factory()->create([
            'payment_id' => $payment->id,
            'file_path' => 'payment-proofs/secret.jpg',
            'uploaded_by' => $client->id,
        ]);

        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->getJson("/api/files/payment-proof/{$proof->id}")
            ->assertStatus(403);

        $this->actingAs($client)
            ->getJson("/api/files/payment-proof/{$proof->id}")
            ->assertOk();
    }

    public function test_a_random_client_cannot_fetch_a_providers_qr_image_without_a_qualifying_booking(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-qr/code.jpg', 'fake-bytes');

        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $method = $profile->paymentMethods()->create([
            'type' => 'gcash',
            'account_name' => 'Provider',
            'qr_image_path' => 'payment-qr/code.jpg',
            'is_active' => true,
        ]);

        $randomClient = User::factory()->client()->create();

        $this->actingAs($randomClient)
            ->getJson("/api/files/payment-qr/{$method->id}")
            ->assertStatus(403);

        $this->actingAs($providerUser)
            ->getJson("/api/files/payment-qr/{$method->id}")
            ->assertOk();
    }

    public function test_payment_method_is_hidden_from_the_payment_resource_while_booking_is_pending(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
            'status' => BookingStatus::Pending,
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        $this->actingAs($client)
            ->getJson("/api/bookings/{$booking->id}/payment")
            ->assertOk()
            ->assertJsonPath('data.payment_method', null);
    }
}
