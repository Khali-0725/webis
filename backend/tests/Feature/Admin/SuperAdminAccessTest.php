<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_an_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/admin/users', [
            'first_name' => 'Other',
            'last_name' => 'Admin',
            'email' => 'other-admin@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'other-admin@example.com']);
    }

    public function test_admin_cannot_update_or_delete_an_admin_account(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($actor)->patchJson("/api/admin/users/{$target->id}", ['first_name' => 'Changed'])->assertForbidden();
        $this->actingAs($actor)->deleteJson("/api/admin/users/{$target->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'first_name' => $target->first_name, 'deleted_at' => null]);
    }

    public function test_admin_cannot_promote_a_client_or_permanently_delete_a_trashed_user(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->client()->create();

        $this->actingAs($actor)->patchJson("/api/admin/users/{$target->id}", ['role' => 'admin'])->assertForbidden();
        $target->delete();
        $this->actingAs($actor)->deleteJson("/api/admin/users/{$target->id}/permanent", ['confirmation' => 'DELETE'])->assertForbidden();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_change_public_announcement(): void
    {
        $actor = User::factory()->admin()->create();
        SystemSetting::create(['key' => 'platform.announcement', 'value' => 'Original']);

        $this->actingAs($actor)->putJson('/api/admin/settings', [
            'settings' => [['key' => 'platform.announcement', 'value' => 'Changed']],
        ])->assertForbidden();

        $this->assertSame('Original', SystemSetting::getValue('platform.announcement'));
    }

    public function test_admin_cannot_change_landing_site_name(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::create(['key' => 'platform.name', 'value' => 'WEBIS']);

        $this->actingAs($admin)->putJson('/api/admin/settings', [
            'settings' => [['key' => 'platform.name', 'value' => 'Other Site']],
        ])->assertForbidden();

        $this->assertSame('WEBIS', SystemSetting::getValue('platform.name'));
    }

    public function test_only_super_admin_can_list_and_restore_non_review_archives(): void
    {
        $admin = User::factory()->admin()->create();
        $super = User::factory()->superAdmin()->create();
        $target = User::factory()->client()->create();
        $target->delete();

        $this->actingAs($admin)->getJson('/api/admin/users?trashed=only')->assertForbidden();
        $this->actingAs($admin)->getJson("/api/admin/users/{$target->id}")->assertForbidden();
        $this->actingAs($admin)->postJson("/api/admin/users/{$target->id}/restore")->assertForbidden();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->actingAs($super)->getJson('/api/admin/users?trashed=only')->assertOk();
        $this->actingAs($super)->postJson("/api/admin/users/{$target->id}/restore")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    public function test_super_admin_cannot_view_or_moderate_admin_reviews(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($super)->postJson('/api/admin/records/reviews/bulk-archive', ['ids' => [1], 'reason' => 'Some reason'])
            ->assertForbidden();
    }

    public function test_super_admin_can_create_an_admin_and_change_announcement(): void
    {
        $actor = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($actor)->postJson('/api/admin/users', [
            'first_name' => 'Other',
            'last_name' => 'Admin',
            'email' => 'other-admin@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ])->assertCreated();

        $this->actingAs($actor)->putJson('/api/admin/settings', [
            'settings' => [['key' => 'platform.announcement', 'value' => 'Notice']],
        ])->assertOk();

        $this->assertSame('Notice', SystemSetting::getValue('platform.announcement'));
    }
}
