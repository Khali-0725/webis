<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingStateMachineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Booking}
     */
    private function pendingBooking(): array
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $client = User::factory()->client()->create();
        $booking = Booking::factory()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        return [$client, $providerUser, $booking];
    }

    public function test_provider_can_accept_a_pending_booking(): void
    {
        [, $providerUser, $booking] = $this->pendingBooking();

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'accepted',
        ]);
    }

    public function test_client_cannot_accept_their_own_booking(): void
    {
        [$client, , $booking] = $this->pendingBooking();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/accept")
            ->assertStatus(403);
    }

    public function test_client_can_cancel_a_pending_booking(): void
    {
        [$client, , $booking] = $this->pendingBooking();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_provider_cannot_cancel_a_still_pending_booking(): void
    {
        [, $providerUser, $booking] = $this->pendingBooking();

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertStatus(403);
    }

    public function test_cancelling_an_accepted_booking_requires_a_reason(): void
    {
        [$client, , $booking] = $this->pendingBooking();
        $booking->forceFill(['status' => \App\Enums\BookingStatus::Accepted, 'accepted_at' => now()])->save();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/cancel", [])
            ->assertStatus(422);

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/cancel", ['reason' => 'Change of plans'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_provider_can_progress_an_accepted_booking_to_completed(): void
    {
        [, $providerUser, $booking] = $this->pendingBooking();
        $booking->forceFill(['status' => \App\Enums\BookingStatus::Accepted, 'accepted_at' => now()])->save();

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/status", ['to' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/status", ['to' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'completed']);
        $this->assertDatabaseCount('booking_status_histories', 2);
    }

    public function test_completing_a_booking_increments_the_providers_completed_count(): void
    {
        [, $providerUser, $booking] = $this->pendingBooking();
        $profile = $providerUser->providerProfile;
        $profile->forceFill(['completed_bookings_count' => 2])->save();
        $booking->forceFill(['status' => \App\Enums\BookingStatus::Accepted, 'accepted_at' => now()])->save();

        $this->actingAs($providerUser)->postJson("/api/bookings/{$booking->id}/status", ['to' => 'in_progress'])->assertOk();
        $this->actingAs($providerUser)->postJson("/api/bookings/{$booking->id}/status", ['to' => 'completed'])->assertOk();

        $this->assertSame(3, $profile->fresh()->completed_bookings_count);
    }

    public function test_an_already_rejected_booking_cannot_be_accepted(): void
    {
        [, $providerUser, $booking] = $this->pendingBooking();
        $booking->forceFill(['status' => \App\Enums\BookingStatus::Rejected])->save();

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/accept")
            ->assertStatus(409);
    }

    public function test_a_stranger_cannot_view_or_act_on_someone_elses_booking(): void
    {
        [, , $booking] = $this->pendingBooking();
        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertStatus(403);

        $this->actingAs($stranger)
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertStatus(403);
    }
}
