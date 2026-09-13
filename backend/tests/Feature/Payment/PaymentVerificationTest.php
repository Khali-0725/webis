<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Payment}
     */
    private function proofSubmittedPayment(): array
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $payment = Payment::factory()->proofSubmitted()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        return [$client, $providerUser, $payment];
    }

    /**
     * @return array{0: User, 1: User, 2: Payment}
     */
    private function pendingCashPayment(): array
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $payment = Payment::factory()->cash()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        return [$client, $providerUser, $payment];
    }

    public function test_the_provider_can_verify_a_submitted_payment(): void
    {
        [, $providerUser, $payment] = $this->proofSubmittedPayment();

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertOk()
            ->assertJsonPath('data.status', 'verified');
    }

    public function test_the_provider_can_verify_a_pending_cash_payment_directly(): void
    {
        [, $providerUser, $payment] = $this->pendingCashPayment();

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertOk()
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.settlement_method', 'cash');
    }

    public function test_a_pending_online_payment_cannot_be_verified_without_a_submitted_proof(): void
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

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertStatus(409);
    }

    public function test_the_provider_can_reject_a_submitted_payment(): void
    {
        [, $providerUser, $payment] = $this->proofSubmittedPayment();

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/reject", ['reason' => 'Amount does not match.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Amount does not match.');
    }

    public function test_admin_can_also_verify_as_a_logged_override(): void
    {
        [, , $payment] = $this->proofSubmittedPayment();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertOk()
            ->assertJsonPath('data.status', 'verified');
    }

    public function test_a_stranger_cannot_verify_someone_elses_payment(): void
    {
        [, , $payment] = $this->proofSubmittedPayment();
        $strangerProviderUser = User::factory()->provider()->create();
        ProviderProfile::factory()->verified()->create(['user_id' => $strangerProviderUser->id]);

        $this->actingAs($strangerProviderUser)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertStatus(403);
    }

    public function test_a_verified_payment_is_immutable(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        [$client, $providerUser, $payment] = $this->proofSubmittedPayment();

        $this->actingAs($providerUser)->postJson("/api/payments/{$payment->id}/verify")->assertOk();

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/verify")
            ->assertStatus(409);

        $this->actingAs($providerUser)
            ->postJson("/api/payments/{$payment->id}/reject", ['reason' => 'Changed my mind.'])
            ->assertStatus(409);

        $this->actingAs($client)
            ->post("/api/bookings/{$payment->booking_id}/payment/proof", [
                'proof' => \Illuminate\Http\UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertStatus(409);
    }
}
