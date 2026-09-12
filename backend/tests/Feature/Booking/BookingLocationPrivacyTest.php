<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Barangay;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingLocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function bookingWithLocation(BookingStatus $status): array
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $client = User::factory()->client()->create();
        $booking = Booking::factory()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
            'status' => $status,
        ]);
        BookingLocation::create([
            'booking_id' => $booking->id,
            'barangay_id' => Barangay::factory()->create()->id,
            'address_line' => '123 Test Street',
            'latitude' => 14.3,
            'longitude' => 120.85,
        ]);

        return [$client, $providerUser, $booking];
    }

    public function test_client_always_sees_the_exact_location(): void
    {
        [$client, , $booking] = $this->bookingWithLocation(BookingStatus::Pending);

        $this->actingAs($client)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.location.address_line', fn ($value) => $value !== null);
    }

    public function test_provider_cannot_see_the_location_while_pending(): void
    {
        [, $providerUser, $booking] = $this->bookingWithLocation(BookingStatus::Pending);

        $this->actingAs($providerUser)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.location', null);

        $this->actingAs($providerUser)
            ->getJson("/api/bookings/{$booking->id}/location")
            ->assertStatus(403);
    }

    public function test_provider_can_see_the_location_once_accepted(): void
    {
        [, $providerUser, $booking] = $this->bookingWithLocation(BookingStatus::Accepted);

        $this->actingAs($providerUser)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.location.address_line', fn ($value) => $value !== null);

        $this->actingAs($providerUser)
            ->getJson("/api/bookings/{$booking->id}/location")
            ->assertOk();
    }

    public function test_admin_viewing_the_location_writes_an_audit_log(): void
    {
        [, , $booking] = $this->bookingWithLocation(BookingStatus::Pending);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson("/api/bookings/{$booking->id}/location")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'booking.location.viewed',
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
        ]);
    }

    public function test_a_different_provider_cannot_view_the_booking_at_all(): void
    {
        [, , $booking] = $this->bookingWithLocation(BookingStatus::Accepted);
        $otherProvider = User::factory()->provider()->create();
        ProviderProfile::factory()->verified()->create(['user_id' => $otherProvider->id]);

        $this->actingAs($otherProvider)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertStatus(403);
    }
}
