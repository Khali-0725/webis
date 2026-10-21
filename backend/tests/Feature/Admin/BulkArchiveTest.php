<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_bulk_archive_eligible_services_with_per_record_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();
        $services = Service::factory()->count(2)->create();
        $ids = $services->pluck('id')->all();

        $this->actingAs($client)->postJson('/api/admin/records/services/bulk-archive', ['ids' => $ids])
            ->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/records/services/bulk-archive', ['ids' => $ids])
            ->assertOk()->assertJsonPath('data.archived_count', 2);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('services', ['id' => $id]);
            $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'service.deleted', 'auditable_id' => $id]);
        }
    }

    public function test_bulk_archive_is_atomic_when_selection_contains_an_ineligible_record(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Service::factory()->create();
        $second = Service::factory()->create();
        $second->delete();

        $this->actingAs($admin)->postJson('/api/admin/records/services/bulk-archive', ['ids' => [$first->id, $second->id]])
            ->assertStatus(409);

        $this->assertNotSoftDeleted('services', ['id' => $first->id]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'service.deleted', 'auditable_id' => $first->id]);
    }

    public function test_bulk_review_archive_requires_reason_and_restores_hidden(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->create(['is_visible' => true]);

        $this->actingAs($admin)->postJson('/api/admin/records/reviews/bulk-archive', ['ids' => [$review->id]])
            ->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/admin/records/reviews/bulk-archive', [
            'ids' => [$review->id], 'reason' => 'Contains personal contact details.',
        ])->assertOk()->assertJsonPath('data.archived_count', 1);

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.archived', 'auditable_id' => $review->id]);
        $this->actingAs($admin)->postJson("/api/admin/reviews/{$review->id}/restore")
            ->assertOk()->assertJsonPath('data.is_visible', false);
    }
}
