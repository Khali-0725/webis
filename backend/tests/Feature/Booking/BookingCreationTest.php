<?php

namespace Tests\Feature\Booking;

use App\Models\Barangay;
use App\Models\Payment;
use App\Models\ProviderAvailabilityRule;
use App\Models\ProviderPaymentMethod;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: ProviderProfile, 2: Service}
     */
    private function verifiedProviderWithService(int $durationMinutes = 60): array
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $service = Service::factory()->published()->create([
            'provider_profile_id' => $profile->id,
            'duration_minutes' => $durationMinutes,
        ]);

        return [$providerUser, $profile, $service];
    }

    private function futureDateOnWeekday(int $dayOfWeek): Carbon
    {
        $date = now()->addDays(7);

        while ($date->dayOfWeek !== $dayOfWeek) {
            $date->addDay();
        }

        return $date;
    }

    private function bookingPayload(Service $service, Carbon $date, string $start = '10:00'): array
    {
        $barangay = Barangay::factory()->create();

        return [
            'service_id' => $service->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_start_time' => $start,
            'barangay_id' => $barangay->id,
            'address_line' => '123 Test Street',
            'latitude' => 14.3,
            'longitude' => 120.85,
            'settlement_method' => 'cash',
        ];
    }

    public function test_client_can_book_an_available_slot(): void
    {
        [, $profile, $service] = $this->verifiedProviderWithService();
        $client = User::factory()->client()->create();

        $date = $this->futureDateOnWeekday(3);
        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $this->actingAs($client)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('bookings', [
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('booking_locations', 1);
        $this->assertDatabaseCount('booking_status_histories', 1);
    }

    public function test_client_can_book_with_cash_settlement_without_a_provider_payment_method(): void
    {
        [, $profile, $service] = $this->verifiedProviderWithService();
        $client = User::factory()->client()->create();

        $date = $this->futureDateOnWeekday(3);
        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $payload = $this->bookingPayload($service, $date, '10:00');
        $payload['settlement_method'] = 'cash';

        $response = $this->actingAs($client)->postJson('/api/bookings', $payload)->assertStatus(201);

        $payment = Payment::where('booking_id', $response->json('data.id'))->firstOrFail();
        $this->assertSame('cash', $payment->settlement_method->value);
        $this->assertNull($payment->provider_payment_method_id);
    }

    public function test_client_can_book_with_online_settlement_when_provider_has_a_payment_method(): void
    {
        [, $profile, $service] = $this->verifiedProviderWithService();
        $client = User::factory()->client()->create();
        $method = ProviderPaymentMethod::factory()->create(['provider_profile_id' => $profile->id]);

        $date = $this->futureDateOnWeekday(3);
        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $payload = $this->bookingPayload($service, $date, '10:00');
        $payload['settlement_method'] = 'online';

        $response = $this->actingAs($client)->postJson('/api/bookings', $payload)->assertStatus(201);

        $payment = Payment::where('booking_id', $response->json('data.id'))->firstOrFail();
        $this->assertSame('online', $payment->settlement_method->value);
        $this->assertSame($method->id, $payment->provider_payment_method_id);
    }

    public function test_booking_online_without_a_configured_provider_payment_method_is_rejected(): void
    {
        [, $profile, $service] = $this->verifiedProviderWithService();
        $client = User::factory()->client()->create();
        // No ProviderPaymentMethod created for this provider.

        $date = $this->futureDateOnWeekday(3);
        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $payload = $this->bookingPayload($service, $date, '10:00');
        $payload['settlement_method'] = 'online';

        $this->actingAs($client)->postJson('/api/bookings', $payload)->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_double_booking_the_same_slot_is_rejected(): void
    {
        [, $profile, $service] = $this->verifiedProviderWithService();
        $firstClient = User::factory()->client()->create();
        $secondClient = User::factory()->client()->create();

        $date = $this->futureDateOnWeekday(3);
        ProviderAvailabilityRule::factory()->create([
            'provider_profile_id' => $profile->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $this->actingAs($firstClient)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(201);

        $this->actingAs($secondClient)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_outside_availability_rules_is_rejected(): void
    {
        [, , $service] = $this->verifiedProviderWithService();
        $client = User::factory()->client()->create();

        $date = $this->futureDateOnWeekday(3);
        // No ProviderAvailabilityRule created for this provider at all.

        $this->actingAs($client)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_an_unverified_providers_service_is_refused(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]); // pending, not verified
        $service = Service::factory()->published()->create(['provider_profile_id' => $profile->id]);
        $client = User::factory()->client()->create();

        $date = $this->futureDateOnWeekday(3);

        $this->actingAs($client)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_provider_cannot_book_a_service(): void
    {
        [, , $service] = $this->verifiedProviderWithService();
        $otherProvider = User::factory()->provider()->create();

        $date = $this->futureDateOnWeekday(3);

        $this->actingAs($otherProvider)
            ->postJson('/api/bookings', $this->bookingPayload($service, $date, '10:00'))
            ->assertStatus(403);
    }
}
