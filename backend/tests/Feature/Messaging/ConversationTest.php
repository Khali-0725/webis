<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
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
}
