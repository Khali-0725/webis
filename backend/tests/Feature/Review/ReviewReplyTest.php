<?php

namespace Tests\Feature\Review;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_cannot_post_or_remove_a_reply_but_historical_reply_remains_visible(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $review = Review::factory()->withReply()->create(['provider_profile_id' => $profile->id]);
        $originalReply = $review->provider_reply;

        $this->actingAs($providerUser)
            ->postJson("/api/reviews/{$review->id}/reply", ['provider_reply' => 'Thank you for booking!'])
            ->assertStatus(405);
        $this->actingAs($providerUser)
            ->deleteJson("/api/reviews/{$review->id}/reply")
            ->assertStatus(405);
        $this->actingAs($providerUser)->getJson('/api/provider/reviews')
            ->assertOk()->assertJsonPath('data.0.provider_reply', $originalReply);
        $this->assertSame($originalReply, $review->fresh()->provider_reply);
    }

    public function test_a_different_provider_has_no_reply_endpoint_either(): void
    {
        $ownerProfile = ProviderProfile::factory()->create();
        $review = Review::factory()->create(['provider_profile_id' => $ownerProfile->id]);

        $intruderUser = User::factory()->provider()->create();
        ProviderProfile::factory()->verified()->create(['user_id' => $intruderUser->id]);

        $this->actingAs($intruderUser)
            ->postJson("/api/reviews/{$review->id}/reply", ['provider_reply' => 'Hijacked'])
            ->assertStatus(405);
    }
}
