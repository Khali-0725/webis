<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnreadCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_a_conversation_zeroes_the_readers_counter_and_stamps_read_at(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
            'client_unread_count' => 2,
            'provider_unread_count' => 1,
        ]);

        $fromProvider = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $providerUser->id,
        ]);
        $fromClient = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/read")
            ->assertOk();

        $conversation->refresh();
        $this->assertSame(0, $conversation->client_unread_count);
        $this->assertSame(1, $conversation->provider_unread_count); // untouched - that's the provider's own counter

        $this->assertNotNull($fromProvider->fresh()->read_at);
        $this->assertNull($fromClient->fresh()->read_at); // never mark the reader's own messages as "read"
    }

    public function test_unread_count_endpoint_sums_across_conversations(): void
    {
        $client = User::factory()->client()->create();
        Conversation::factory()->create(['client_id' => $client->id, 'client_unread_count' => 2]);
        Conversation::factory()->create(['client_id' => $client->id, 'client_unread_count' => 3]);

        $this->actingAs($client)
            ->getJson('/api/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 5);
    }
}
