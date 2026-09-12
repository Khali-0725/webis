<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_toggle_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $created = $this->actingAs($admin)->postJson('/api/admin/categories', [
            'name' => 'Electrical Work',
        ])->assertStatus(201)->assertJsonPath('data.slug', 'electrical-work');

        $categoryId = $created->json('data.id');

        $this->actingAs($admin)
            ->patchJson("/api/admin/categories/{$categoryId}", ['description' => 'Wiring and repairs'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Wiring and repairs');

        $this->actingAs($admin)
            ->patchJson("/api/admin/categories/{$categoryId}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_non_admin_cannot_manage_categories(): void
    {
        $provider = User::factory()->provider()->create();
        $category = ServiceCategory::factory()->create();

        $this->actingAs($provider)->postJson('/api/admin/categories', ['name' => 'x'])->assertStatus(403);
        $this->actingAs($provider)->patchJson("/api/admin/categories/{$category->id}/toggle")->assertStatus(403);
    }
}
