<?php

namespace Tests\Feature\Messaging;

use App\Models\ChatViolation;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageSendingTest extends TestCase
{
    use RefreshDatabase;

    private function conversation(): array
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);

        return [$client, $providerUser, $conversation];
    }

    public function test_a_clean_message_persists_and_increments_the_recipients_unread_count(): void
    {
        [$client, , $conversation] = $this->conversation();

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hi, when can you start?'])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'body' => 'Hi, when can you start?',
            'moderation_status' => 'allowed',
        ]);

        $conversation->refresh();
        $this->assertSame(0, $conversation->client_unread_count);
        $this->assertSame(1, $conversation->provider_unread_count);
    }

    public function test_a_blocked_message_is_refused_and_never_persisted(): void
    {
        [$client, , $conversation] = $this->conversation();

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Call me on 09171234567'])
            ->assertStatus(422);

        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseHas('chat_violations', [
            'conversation_id' => $conversation->id,
            'action_taken' => 'blocked',
        ]);
    }

    public function test_a_warn_tier_message_requires_confirmation_before_it_is_sent(): void
    {
        [$client, , $conversation] = $this->conversation();

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'my number is 1234567'])
            ->assertOk()
            ->assertJsonPath('data.requires_confirmation', true);

        $this->assertDatabaseCount('messages', 0);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'my number is 1234567',
                'confirm_override' => true,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'moderation_status' => 'warned']);
        $this->assertDatabaseHas('chat_violations', ['conversation_id' => $conversation->id, 'action_taken' => 'warned']);
    }

    public function test_repeated_warnings_within_24_hours_escalate_the_next_message_to_flagged(): void
    {
        [$client, , $conversation] = $this->conversation();

        ChatViolation::factory()->count(3)->warned()->create([
            'user_id' => $client->id,
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Sounds good, see you then!'])
            ->assertStatus(201);

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'moderation_status' => 'flagged']);
        $this->assertDatabaseHas('chat_violations', ['conversation_id' => $conversation->id, 'action_taken' => 'flagged']);
    }
}
