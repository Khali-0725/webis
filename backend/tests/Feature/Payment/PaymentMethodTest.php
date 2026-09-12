<?php

namespace Tests\Feature\Payment;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function providerWithProfile(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_provider_can_add_a_payment_method_with_a_qr_image(): void
    {
        Storage::fake('local');
        [$user] = $this->providerWithProfile();

        $response = $this->actingAs($user)->post('/api/provider/payment-methods', [
            'type' => 'gcash',
            'account_name' => 'Juan Dela Cruz',
            'account_ref_masked' => '**** 1234',
            'is_default' => true,
            'qr_image' => UploadedFile::fake()->image('qr.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.qr_image_url'));
        $this->assertDatabaseHas('provider_payment_methods', [
            'account_name' => 'Juan Dela Cruz',
            'is_default' => true,
        ]);
    }

    public function test_bank_transfer_does_not_require_a_qr_image(): void
    {
        [$user] = $this->providerWithProfile();

        $this->actingAs($user)->postJson('/api/provider/payment-methods', [
            'type' => 'bank',
            'account_name' => 'Juan Dela Cruz',
            'account_ref_masked' => 'BPI **** 5678',
        ])->assertStatus(201);
    }

    public function test_qr_based_method_requires_a_qr_image(): void
    {
        [$user] = $this->providerWithProfile();

        $this->actingAs($user)->postJson('/api/provider/payment-methods', [
            'type' => 'gcash',
            'account_name' => 'Juan Dela Cruz',
        ])->assertStatus(422);
    }

    public function test_setting_a_new_default_unsets_the_previous_one(): void
    {
        Storage::fake('local');
        [$user, $profile] = $this->providerWithProfile();

        $first = $profile->paymentMethods()->create([
            'type' => 'gcash',
            'account_name' => 'First',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson('/api/provider/payment-methods', [
            'type' => 'bank',
            'account_name' => 'Second',
            'is_default' => true,
        ])->assertStatus(201);

        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_a_provider_cannot_update_another_providers_payment_method(): void
    {
        [, $ownerProfile] = $this->providerWithProfile();
        [$intruder] = $this->providerWithProfile();

        $method = $ownerProfile->paymentMethods()->create([
            'type' => 'bank',
            'account_name' => 'Owner',
            'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->patchJson("/api/provider/payment-methods/{$method->id}", ['account_name' => 'Hijacked'])
            ->assertStatus(403);
    }
}
