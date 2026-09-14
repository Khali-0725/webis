<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Payment;
use App\Models\ProviderAvailabilityException;
use App\Models\ProviderPaymentMethod;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client/provider-owned side of "full CRUD with soft delete": each
 * owner can update and (soft) delete their own records, a stranger cannot,
 * and the row is retained with a deleted_at stamp.
 */
class OwnerCrudSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): array
    {
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    // ------------------------------------------------------------ Provider: services

    public function test_provider_soft_deletes_and_restores_their_own_service(): void
    {
        [$user, $profile] = $this->provider();
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($user)->deleteJson("/api/provider/services/{$service->id}")->assertOk();
        $this->assertSoftDeleted('services', ['id' => $service->id]);

        $this->actingAs($user)->getJson('/api/provider/services')->assertJsonMissing(['id' => $service->id]);
        $this->actingAs($user)->getJson('/api/provider/services?trashed=only')->assertJsonFragment(['id' => $service->id]);
        $this->getJson("/api/services/{$service->id}")->assertNotFound();

        $this->actingAs($user)->postJson("/api/provider/services/{$service->id}/restore")->assertOk();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
    }

    public function test_provider_cannot_delete_another_providers_service(): void
    {
        [$user] = $this->provider();
        $service = Service::factory()->create();

        $this->actingAs($user)->deleteJson("/api/provider/services/{$service->id}")->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
    }

    // ------------------------------------------------------------ Provider: payment methods

    public function test_provider_soft_deletes_a_payment_method_and_the_next_one_becomes_default(): void
    {
        [$user, $profile] = $this->provider();
        $default = ProviderPaymentMethod::factory()->create(['provider_profile_id' => $profile->id, 'is_default' => true]);
        $other = ProviderPaymentMethod::factory()->create(['provider_profile_id' => $profile->id, 'is_default' => false]);

        $this->actingAs($user)->deleteJson("/api/provider/payment-methods/{$default->id}")->assertOk();

        $this->assertSoftDeleted('provider_payment_methods', ['id' => $default->id]);
        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_a_payment_method_with_a_pending_online_payment_cannot_be_deleted(): void
    {
        [$user, $profile] = $this->provider();
        $method = ProviderPaymentMethod::factory()->create(['provider_profile_id' => $profile->id]);
        $booking = Booking::factory()->accepted()->create(['provider_profile_id' => $profile->id]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'provider_profile_id' => $profile->id,
            'provider_payment_method_id' => $method->id,
        ]);

        $this->actingAs($user)->deleteJson("/api/provider/payment-methods/{$method->id}")->assertStatus(409);
    }

    public function test_a_stranger_cannot_delete_a_payment_method(): void
    {
        [$user] = $this->provider();
        $method = ProviderPaymentMethod::factory()->create();

        $this->actingAs($user)->deleteJson("/api/provider/payment-methods/{$method->id}")->assertForbidden();
    }

    // ------------------------------------------------------------ Provider: availability exceptions

    public function test_provider_updates_an_exception_and_re_adding_a_deleted_date_revives_it(): void
    {
        [$user, $profile] = $this->provider();
        $date = now()->addDays(3)->toDateString();

        $id = $this->actingAs($user)
            ->postJson('/api/provider/availability/exceptions', ['date' => $date, 'is_closed' => true])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->patchJson("/api/provider/availability/exceptions/{$id}", ['is_closed' => false, 'start_time' => '13:00', 'end_time' => '17:00'])
            ->assertOk()
            ->assertJsonPath('data.is_closed', false);

        $this->actingAs($user)->deleteJson("/api/provider/availability/exceptions/{$id}")->assertOk();
        $this->assertSoftDeleted('provider_availability_exceptions', ['id' => $id]);

        // Same date again: UNIQUE(provider_profile_id, date) must not blow up.
        $this->actingAs($user)
            ->postJson('/api/provider/availability/exceptions', ['date' => $date, 'is_closed' => true])
            ->assertCreated()
            ->assertJsonPath('data.id', $id);

        $this->assertDatabaseHas('provider_availability_exceptions', ['id' => $id, 'deleted_at' => null]);
        $this->assertSame(1, ProviderAvailabilityException::withTrashed()->where('provider_profile_id', $profile->id)->count());
    }

    // ------------------------------------------------------------ Client: bookings

    public function test_client_can_edit_notes_on_their_pending_booking_but_not_after_acceptance(): void
    {
        $client = User::factory()->client()->create();
        $pending = Booking::factory()->create(['client_id' => $client->id]);
        $accepted = Booking::factory()->accepted()->create(['client_id' => $client->id]);

        $this->actingAs($client)
            ->patchJson("/api/bookings/{$pending->id}", ['client_notes' => 'Gate code is 1234'])
            ->assertOk()
            ->assertJsonPath('data.client_notes', 'Gate code is 1234');

        $this->actingAs($client)
            ->patchJson("/api/bookings/{$accepted->id}", ['client_notes' => 'Too late'])
            ->assertForbidden();
    }

    public function test_client_soft_deletes_a_finished_booking_but_not_a_live_one(): void
    {
        $client = User::factory()->client()->create();
        $done = Booking::factory()->completed()->create(['client_id' => $client->id]);
        $live = Booking::factory()->accepted()->create(['client_id' => $client->id]);

        $this->actingAs($client)->deleteJson("/api/bookings/{$done->id}")->assertOk();
        $this->assertSoftDeleted('bookings', ['id' => $done->id]);

        $this->actingAs($client)->deleteJson("/api/bookings/{$live->id}")->assertForbidden();
        $this->assertDatabaseHas('bookings', ['id' => $live->id, 'deleted_at' => null]);

        // Restore is admin-only.
        $this->actingAs($client)->postJson("/api/bookings/{$done->id}/restore")->assertForbidden();
    }

    public function test_provider_cannot_delete_a_clients_booking(): void
    {
        [$user, $profile] = $this->provider();
        $booking = Booking::factory()->completed()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($user)->deleteJson("/api/bookings/{$booking->id}")->assertForbidden();
    }

    // ------------------------------------------------------------ Client: reviews

    public function test_client_updates_and_soft_deletes_their_own_review_and_aggregates_follow(): void
    {
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->verified()->create();
        $review = Review::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id, 'rating' => 5]);

        $this->actingAs($client)
            ->patchJson("/api/reviews/{$review->id}", ['rating' => 2, 'comment' => 'Changed my mind'])
            ->assertOk()
            ->assertJsonPath('data.rating', 2);

        $this->assertEquals(2.0, (float) $profile->fresh()->rating_avg);

        $this->actingAs($client)->deleteJson("/api/reviews/{$review->id}")->assertOk();
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        $this->assertSame(0, $profile->fresh()->rating_count);
    }

    public function test_re_reviewing_after_deleting_revives_the_same_row(): void
    {
        $client = User::factory()->client()->create();
        $booking = Booking::factory()->completed()->create(['client_id' => $client->id]);
        $review = Review::factory()->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_profile_id' => $booking->provider_profile_id,
        ]);
        $review->delete();

        $this->actingAs($client)
            ->postJson("/api/bookings/{$booking->id}/review", ['rating' => 4, 'comment' => 'Second thoughts'])
            ->assertCreated()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.rating', 4);
    }

    public function test_a_different_client_cannot_edit_or_delete_a_review(): void
    {
        $stranger = User::factory()->client()->create();
        $review = Review::factory()->create();

        $this->actingAs($stranger)->patchJson("/api/reviews/{$review->id}", ['rating' => 1])->assertForbidden();
        $this->actingAs($stranger)->deleteJson("/api/reviews/{$review->id}")->assertForbidden();
    }

    public function test_provider_can_remove_their_reply(): void
    {
        [$user, $profile] = $this->provider();
        $review = Review::factory()->withReply()->create(['provider_profile_id' => $profile->id]);

        $this->actingAs($user)
            ->deleteJson("/api/reviews/{$review->id}/reply")
            ->assertOk()
            ->assertJsonPath('data.provider_reply', null);
    }

    // ------------------------------------------------------------ Messages

    public function test_sender_edits_and_unsends_their_own_message(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create([
            'client_id' => $client->id,
            'provider_user_id' => $providerUser->id,
            'provider_unread_count' => 1,
        ]);
        $message = Message::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $client->id]);

        $this->actingAs($client)
            ->patchJson("/api/conversations/{$conversation->id}/messages/{$message->id}", ['body' => 'Edited body'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Edited body');

        // Editing runs the same filter as sending.
        $this->actingAs($client)
            ->patchJson("/api/conversations/{$conversation->id}/messages/{$message->id}", ['body' => 'call me 09171234567'])
            ->assertStatus(422);

        $this->actingAs($client)
            ->deleteJson("/api/conversations/{$conversation->id}/messages/{$message->id}")
            ->assertOk();

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
        $this->assertSame(0, $conversation->fresh()->provider_unread_count, 'an unread unsent message no longer counts');
    }

    public function test_recipient_cannot_edit_or_delete_the_senders_message(): void
    {
        $client = User::factory()->client()->create();
        $providerUser = User::factory()->provider()->create();
        $conversation = Conversation::factory()->create(['client_id' => $client->id, 'provider_user_id' => $providerUser->id]);
        $message = Message::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $client->id]);

        $this->actingAs($providerUser)
            ->patchJson("/api/conversations/{$conversation->id}/messages/{$message->id}", ['body' => 'Hijack'])
            ->assertForbidden();
        $this->actingAs($providerUser)
            ->deleteJson("/api/conversations/{$conversation->id}/messages/{$message->id}")
            ->assertForbidden();
    }

    // ------------------------------------------------------------ Reports (reporter side)

    public function test_reporter_lists_edits_and_withdraws_an_open_report(): void
    {
        $client = User::factory()->client()->create();
        $target = User::factory()->provider()->create();

        $id = $this->actingAs($client)
            ->postJson('/api/reports', ['reportable_type' => 'user', 'reportable_id' => $target->id, 'reason' => 'other'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($client)->getJson('/api/reports')->assertOk()->assertJsonFragment(['id' => $id]);

        $this->actingAs($client)
            ->patchJson("/api/reports/{$id}", ['details' => 'More detail'])
            ->assertOk()
            ->assertJsonPath('data.details', 'More detail');

        $this->actingAs($client)->deleteJson("/api/reports/{$id}")->assertOk();
        $this->assertSoftDeleted('reports', ['id' => $id]);
    }

    public function test_a_report_already_being_reviewed_cannot_be_edited_by_the_reporter(): void
    {
        $client = User::factory()->client()->create();
        $report = Report::create([
            'reporter_id' => $client->id,
            'reportable_type' => User::class,
            'reportable_id' => User::factory()->provider()->create()->id,
            'reason' => 'other',
        ]);
        $report->forceFill(['status' => 'reviewing'])->save();

        $this->actingAs($client)->patchJson("/api/reports/{$report->id}", ['details' => 'x'])->assertForbidden();
        $this->actingAs($client)->deleteJson("/api/reports/{$report->id}")->assertForbidden();

        $stranger = User::factory()->client()->create();
        $this->actingAs($stranger)->getJson("/api/reports/{$report->id}")->assertForbidden();
    }

    // ------------------------------------------------------------ Own account

    public function test_a_user_can_delete_their_own_account_with_their_password(): void
    {
        $client = User::factory()->client()->create(['password' => 'password123']);

        $this->actingAs($client)->deleteJson('/api/me/account', ['password' => 'wrong'])->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $client->id, 'deleted_at' => null]);

        $this->actingAs($client)->deleteJson('/api/me/account', ['password' => 'password123'])->assertOk();
        $this->assertSoftDeleted('users', ['id' => $client->id]);
    }

    public function test_an_admin_cannot_self_delete(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password123']);

        $this->actingAs($admin)->deleteJson('/api/me/account', ['password' => 'password123'])->assertForbidden();
    }

    public function test_a_deleted_client_still_shows_on_the_providers_booking(): void
    {
        [$user, $profile] = $this->provider();
        $client = User::factory()->client()->create();
        $booking = Booking::factory()->completed()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $client->delete();

        $this->actingAs($user)
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.client.full_name', $client->full_name)
            ->assertJsonPath('data.status', BookingStatus::Completed->value);
    }
}
