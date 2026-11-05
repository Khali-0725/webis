<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_publish_and_edit_a_faq_for_public_view(): void
    {
        $actor = User::factory()->superAdmin()->create();

        $created = $this->actingAs($actor)->postJson('/api/admin/faqs', [
            'question' => 'How do bookings work?',
            'answer' => 'Choose a provider and submit a request.',
            'sort_order' => 2,
            'is_published' => true,
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->getJson('/api/faqs')->assertOk()->assertJsonFragment(['question' => 'How do bookings work?']);

        $this->actingAs($actor)->patchJson("/api/admin/faqs/{$id}", ['answer' => 'Choose a provider and confirm.'])
            ->assertOk()->assertJsonPath('data.answer', 'Choose a provider and confirm.');

        $this->assertDatabaseHas('audit_logs', ['action' => 'faq.updated', 'auditable_id' => $id]);
    }

    public function test_unpublished_faq_is_hidden_from_public_and_admin_is_forbidden(): void
    {
        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $created = $this->actingAs($super)->postJson('/api/admin/faqs', [
            'question' => 'Internal answer?',
            'answer' => 'Hidden until published.',
            'is_published' => false,
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->getJson('/api/faqs')->assertOk()->assertJsonMissing(['question' => 'Internal answer?']);
        $this->actingAs($admin)->getJson('/api/admin/faqs')->assertForbidden();
        $this->actingAs($admin)->patchJson("/api/admin/faqs/{$id}", ['is_published' => true])->assertForbidden();
        $this->actingAs($admin)->deleteJson("/api/admin/faqs/{$id}")->assertForbidden();
    }

    public function test_super_admin_can_remove_a_faq_with_audit_history(): void
    {
        $super = User::factory()->superAdmin()->create();
        $created = $this->actingAs($super)->postJson('/api/admin/faqs', [
            'question' => 'Old answer?',
            'answer' => 'Outdated.',
            'is_published' => true,
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->actingAs($super)->deleteJson("/api/admin/faqs/{$id}")->assertOk();

        $this->assertSoftDeleted('faqs', ['id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'faq.deleted', 'auditable_id' => $id]);
        $this->getJson('/api/faqs')->assertJsonMissing(['question' => 'Old answer?']);
    }
}
