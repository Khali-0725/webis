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

    public function test_provider_can_reply_to_their_own_review(): void
    {
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);
        $review = Review::factory()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($providerUser)
            ->postJson("/api/reviews/{$review->id}/reply", ['provider_reply' => 'Thank you for booking!'])
            ->assertOk()
            ->assertJsonPath('data.provider_reply', 'Thank you for booking!');
    }

    public function test_a_different_provider_cannot_reply(): void
    {
        $ownerProfile = ProviderProfile::factory()->create();
        $review = Review::factory()->create(['provider_profile_id' => $ownerProfile->id]);

        $intruderUser = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $intruderUser->id]);

        $this->actingAs($intruderUser)
            ->postJson("/api/reviews/{$review->id}/reply", ['provider_reply' => 'Hijacked'])
            ->assertStatus(403);
    }
}
