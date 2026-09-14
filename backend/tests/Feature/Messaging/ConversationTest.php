<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_can_start_a_conversation_with_a_provider(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);

        $this->actingAs($client)
            ->postJson('/api/conversations', ['provider_profile_id' => $profile->id])
            ->assertOk()
            ->assertJsonPath('data.other_participant.id', $providerUser->id);

        $this->assertDatabaseHas('conversations', [
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);
    }

    public function test_starting_a_conversation_twice_reuses_the_same_thread(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);

        $first = $this->actingAs($client)
            ->postJson('/api/conversations', ['provider_profile_id' => $profile->id])
            ->json('data.id');

        $second = $this->actingAs($client)
            ->postJson('/api/conversations', ['provider_profile_id' => $profile->id])
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_a_provider_cannot_start_a_conversation_from_a_provider_profile(): void
    {
        $providerUser = User::factory()->provider()->create();
        $otherProvider = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $providerUser->id]);

        $this->actingAs($otherProvider)
            ->postJson('/api/conversations', ['provider_profile_id' => $profile->id])
            ->assertStatus(403);
    }

    public function test_a_stranger_cannot_view_or_post_into_someone_elses_conversation(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);
        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertStatus(403);

        $this->actingAs($stranger)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hello'])
            ->assertStatus(403);

        $this->actingAs($stranger)
            ->postJson("/api/conversations/{$conversation->id}/read")
            ->assertStatus(403);
    }

    public function test_a_participant_can_list_their_conversations(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
            'last_message_at' => now(),
        ]);

        $this->actingAs($client)
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * Regression: the messages() relation carries its own orderBy('id'), so a
     * naive orderByDesc() on top produced ORDER BY id ASC, id DESC and page 1
     * was the OLDEST messages - anything past per_page never rendered.
     */
    public function test_the_first_page_of_a_long_thread_is_the_newest_messages(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);

        Message::factory()->count(20)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
        ]);
        $newest = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
            'body' => 'the newest message',
        ]);

        $response = $this->actingAs($client)
            ->getJson("/api/conversations/{$conversation->id}/messages?per_page=15")
            ->assertOk()
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('data.0.id', $newest->id);

        $this->assertCount(15, $response->json('data'));
    }
}
