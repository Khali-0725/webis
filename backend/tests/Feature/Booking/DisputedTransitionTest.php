<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputedTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_raise_a_dispute_on_an_in_progress_booking(): void
    {
        $booking = Booking::factory()->inProgress()->create();
        $client = $booking->client;

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/status", ['to' => BookingStatus::Disputed->value])
            ->assertOk();

        $this->assertEquals(BookingStatus::Disputed, $booking->fresh()->status);
    }

    public function test_admin_can_raise_a_dispute_on_a_completed_booking(): void
    {
        $booking = Booking::factory()->completed()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/bookings/{$booking->id}/status", ['to' => BookingStatus::Disputed->value])
            ->assertOk();

        $this->assertEquals(BookingStatus::Disputed, $booking->fresh()->status);
    }

    public function test_provider_cannot_raise_a_dispute(): void
    {
        $booking = Booking::factory()->inProgress()->create();
        $provider = $booking->providerProfile->user;

        $this->actingAs($provider)
            ->postJson("/api/bookings/{$booking->id}/status", ['to' => BookingStatus::Disputed->value])
            ->assertForbidden();

        $this->assertEquals(BookingStatus::InProgress, $booking->fresh()->status);
    }
}
