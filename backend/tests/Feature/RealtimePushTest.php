<?php

namespace Tests\Feature;

use App\Events\UserDataChanged;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimePushTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_message_pushes_to_the_recipient_only(): void
    {
        Event::fake([UserDataChanged::class]);

        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hi, when can you start?'])
            ->assertStatus(201);

        Event::assertDispatched(UserDataChanged::class, function (UserDataChanged $event) use ($providerUser, $conversation) {
            return $event->scope === 'messages'
                && $event->userIds === [$providerUser->id]
                && $event->meta === ['conversation_id' => $conversation->id]
                && $event->broadcastOn()[0]->name === "private-App.Models.User.{$providerUser->id}";
        });
    }

    public function test_a_blocked_message_pushes_nothing(): void
    {
        Event::fake([UserDataChanged::class]);

        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
        ]);

        $this->actingAs($client)
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Call me on 09171234567'])
            ->assertStatus(422);

        Event::assertNotDispatched(UserDataChanged::class);
    }

    public function test_a_booking_transition_pushes_to_both_client_and_provider(): void
    {
        Event::fake([UserDataChanged::class]);

        $providerUser = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $providerUser->id]);
        $client = User::factory()->client()->create();
        $booking = Booking::factory()->create([
            'client_id' => $client->id,
            'provider_profile_id' => $profile->id,
        ]);

        $this->actingAs($providerUser)
            ->postJson("/api/bookings/{$booking->id}/accept")
            ->assertOk();

        Event::assertDispatched(UserDataChanged::class, function (UserDataChanged $event) use ($client, $providerUser, $booking) {
            return $event->scope === 'bookings'
                && $event->userIds === [$client->id, $providerUser->id]
                && $event->meta === ['booking_id' => $booking->id];
        });
    }

    public function test_only_the_owner_can_subscribe_to_their_channel(): void
    {
        // The `null` broadcaster the suite runs on skips channel
        // authorization entirely; the Pusher one signs locally without a
        // network call, so dummy keys are enough to exercise channels.php.
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => '1',
            'broadcasting.connections.pusher.options.cluster' => 'ap1',
        ]);

        // channels.php was loaded against the `null` driver at boot; the
        // freshly-resolved pusher driver needs the same registrations.
        require base_path('routes/channels.php');

        $user = User::factory()->client()->create();
        $other = User::factory()->client()->create();

        $this->actingAs($user)
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$user->id}",
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$other->id}",
            ])
            ->assertStatus(403);
    }

    public function test_a_guest_cannot_authenticate_a_channel(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-App.Models.User.1',
        ])->assertStatus(401);
    }
}
