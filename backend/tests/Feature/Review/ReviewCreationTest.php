<?php

namespace Tests\Feature\Review;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: ProviderProfile, 2: Booking}
     */
    private function completedBooking(): array
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->completed()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        return [$client, $profile, $booking];
    }

    public function test_client_can_review_a_completed_booking_and_aggregates_recompute(): void
    {
        [$client, $profile, $booking] = $this->completedBooking();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 5, 'comment' => 'Excellent work!'])
            ->assertStatus(201)
            ->assertJsonPath('data.rating', 5);

        $profile->refresh();
        $this->assertEquals(5.00, (float) $profile->rating_avg);
        $this->assertSame(1, $profile->rating_count);
    }

    public function test_reviewing_before_completed_is_refused(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $booking = Booking::factory()->accepted()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 5])
            ->assertStatus(403);
    }

    public function test_a_second_review_on_the_same_booking_is_refused(): void
    {
        [$client, , $booking] = $this->completedBooking();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 4])
            ->assertStatus(201);

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 2])
            ->assertStatus(409);
    }

    public function test_a_stranger_cannot_review_someone_elses_booking(): void
    {
        [, , $booking] = $this->completedBooking();
        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 5])
            ->assertStatus(403);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        [$client, , $booking] = $this->completedBooking();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 6])
            ->assertStatus(422);
    }
}
