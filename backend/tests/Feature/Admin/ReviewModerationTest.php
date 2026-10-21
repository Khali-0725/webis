<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_hide_archive_and_restore_a_review_with_history(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $review = Review::factory()->create(['client_id' => $client->id, 'rating' => 5]);

        $this->actingAs($client)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($admin)->patchJson("/api/admin/reviews/{$review->id}/visibility", [
            'is_visible' => false, 'reason' => 'Contains a private phone number.',
        ])->assertOk()->assertJsonPath('data.is_visible', false);
        $this->actingAs($admin)->deleteJson("/api/admin/reviews/{$review->id}", [
            'reason' => 'Policy review requires archiving.',
        ])->assertOk();
        $this->actingAs($admin)->getJson('/api/admin/reviews?trashed=only')
            ->assertOk()->assertJsonPath('data.0.id', $review->id);
        $this->actingAs($admin)->postJson("/api/admin/reviews/{$review->id}/restore")
            ->assertOk()->assertJsonPath('data.is_visible', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.archived', 'auditable_id' => $review->id]);
    }
}
