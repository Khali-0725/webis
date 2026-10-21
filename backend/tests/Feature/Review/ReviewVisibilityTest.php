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

    public function test_provider_cannot_hide_or_unhide_a_client_review(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id, 'rating_avg' => 5, 'rating_count' => 1]);
        $review = Review::factory()->create(['provider_profile_id' => $profile->id, 'rating' => 5]);

        $this->actingAs($providerUser)
            ->patchJson("/api/reviews/{$review->id}/visibility", ['is_visible' => false])
            ->assertMethodNotAllowed();

        $profile->refresh();
        $this->assertSame(1, $profile->rating_count);
        $this->assertEquals(5, (float) $profile->rating_avg);
        $this->assertTrue($review->fresh()->is_visible);
    }
}
