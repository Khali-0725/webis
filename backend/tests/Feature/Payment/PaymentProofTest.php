<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Booking, 3: Payment}
     */
    private function bookingWithPayment(): array
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        return [$client, $providerUser, $booking, $payment];
    }

    public function test_client_can_submit_payment_proof(): void
    {
        Storage::fake('local');
        [$client, , $booking] = $this->bookingWithPayment();

        $this->actingAs($client)
            ->post("/api/bookings/{$booking->id}/payment/proof", [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
                'reference_number' => 'REF-12345',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'proof_submitted')
            ->assertJsonPath('data.reference_number', 'REF-12345');

        $this->assertDatabaseCount('payment_proofs', 1);
    }

    public function test_a_php_file_disguised_as_an_image_is_rejected(): void
    {
        Storage::fake('local');
        [$client, , $booking] = $this->bookingWithPayment();

        $this->actingAs($client)
            ->post("/api/bookings/{$booking->id}/payment/proof", [
                'proof' => UploadedFile::fake()->create('proof.php', 10, 'image/jpeg'),
                'reference_number' => 'REF-12345',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('payment_proofs', 0);
    }

    public function test_resubmitting_a_proof_supersedes_the_previous_one(): void
    {
        Storage::fake('local');
        [$client, , $booking] = $this->bookingWithPayment();

        $this->actingAs($client)->post("/api/bookings/{$booking->id}/payment/proof", [
            'proof' => UploadedFile::fake()->image('first.jpg'),
        ])->assertOk();

        $this->actingAs($client)->post("/api/bookings/{$booking->id}/payment/proof", [
            'proof' => UploadedFile::fake()->image('second.jpg'),
        ])->assertOk();

        $this->assertDatabaseCount('payment_proofs', 2);
        $this->assertSame(1, \App\Models\PaymentProof::whereNull('superseded_at')->count());
    }

    public function test_someone_else_cannot_submit_proof_for_a_booking_that_is_not_theirs(): void
    {
        Storage::fake('local');
        [, , $booking] = $this->bookingWithPayment();
        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->post("/api/bookings/{$booking->id}/payment/proof", [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertStatus(403);
    }
}
