<?php

namespace Tests\Feature\Messaging;

use App\Enums\UserStatus;
use App\Models\Conversation;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_greeting_gets_one_labelled_automatic_reply(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->verified()->create(['user_id' => $provider->id]);
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $provider->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hi'])
            ->assertCreated();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $provider->id,
            'is_automated' => true,
        ]);
        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_availability_answer_tracks_provider_account_status(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->verified()->create([
            'user_id' => $provider->id,
            'is_accepting_bookings' => true,
        ]);
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $provider->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Is this service available?'])
            ->assertCreated();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $provider->id,
            'body' => 'The provider is currently accepting bookings. Please check an available time slot before booking.',
            'is_automated' => true,
        ]);

        $provider->forceFill(['status' => UserStatus::Suspended])->save();

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Is this service available?'])
            ->assertCreated();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $provider->id,
            'body' => 'The provider is currently unavailable. Please check again later.',
            'is_automated' => true,
        ]);
    }
}
