<?php

namespace Tests\Feature\Review;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hiding_a_review_excludes_it_from_the_providers_aggregate(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id, 'rating_avg' => 5, 'rating_count' => 1]);
        $review = Review::factory()->create(['provider_profile_id' => $profile->id, 'rating' => 5]);

        $this->actingAs($providerUser)
            ->patchJson("/api/reviews/{$review->id}/visibility", ['is_visible' => false])
            ->assertOk()
            ->assertJsonPath('data.is_visible', false);

        $profile->refresh();
        $this->assertSame(0, $profile->rating_count);
        $this->assertEquals(0, (float) $profile->rating_avg);
    }

    public function test_unhiding_a_review_restores_it_to_the_aggregate(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);
        $review = Review::factory()->hidden()->create(['provider_profile_id' => $profile->id, 'rating' => 4]);

        $this->actingAs($providerUser)
            ->patchJson("/api/reviews/{$review->id}/visibility", ['is_visible' => true])
            ->assertOk();

        $profile->refresh();
        $this->assertSame(1, $profile->rating_count);
        $this->assertEquals(4.00, (float) $profile->rating_avg);
    }

    public function test_a_stranger_provider_cannot_change_visibility(): void
    {
        $ownerProfile = ProviderProfile::factory()->create();
        $review = Review::factory()->create(['provider_profile_id' => $ownerProfile->id]);

        $intruderUser = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $intruderUser->id]);

        $this->actingAs($intruderUser)
            ->patchJson("/api/reviews/{$review->id}/visibility", ['is_visible' => false])
            ->assertStatus(403);
    }
}
