<?php

namespace Tests\Feature\Review;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_provider_reviews_only_returns_visible_ones_with_a_distribution(): void
    {
        $profile = ProviderProfile::factory()->create();
        Review::factory()->create(['provider_profile_id' => $profile->id, 'rating' => 5]);
        Review::factory()->create(['provider_profile_id' => $profile->id, 'rating' => 5]);
        Review::factory()->create(['provider_profile_id' => $profile->id, 'rating' => 3]);
        Review::factory()->hidden()->create(['provider_profile_id' => $profile->id, 'rating' => 1]);

        $response = $this->getJson("/api/providers/{$profile->id}/reviews")->assertOk();

        $response->assertJsonCount(3, 'data');
        $this->assertSame(2, $response->json('meta.distribution.5'));
        $this->assertSame(1, $response->json('meta.distribution.3'));
        $this->assertSame(0, $response->json('meta.distribution.1'));
    }

    public function test_a_provider_sees_their_own_hidden_reviews_via_their_own_endpoint(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);
        Review::factory()->hidden()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($providerUser)
            ->getJson('/api/provider/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_client_can_list_their_own_written_reviews(): void
    {
        $client = User::factory()->client()->create();
        Review::factory()->create(['client_id' => $client->id]);
        Review::factory()->create(); // someone else's review

        $this->actingAs($client)
            ->getJson('/api/me/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
