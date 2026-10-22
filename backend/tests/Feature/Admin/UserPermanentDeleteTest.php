<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\ChatViolation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AccountEventNotification;
use App\Support\AuditLogger;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_purge_a_trashed_account_and_reuse_its_email(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create([
            'email' => 'reusable@example.com',
            'avatar_path' => 'avatars/old.png',
        ]);
        Storage::disk('local')->put('avatars/old.png', 'image');
        $user->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent", ['confirmation' => 'DELETE'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('local')->assertMissing('avatars/old.png');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.permanently_deleted',
            'auditable_id' => $user->id,
        ]);
        User::factory()->client()->create(['email' => 'reusable@example.com']);
    }

    public function test_purge_removes_unused_provider_setup_and_private_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $user->id]);
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);
        $profile->verificationDocuments()->create([
            'document_type' => 'government_id',
            'file_path' => 'verification/old.png',
            'original_name' => 'old.png',
            'mime_type' => 'image/png',
            'size_bytes' => 5,
        ]);
        Storage::disk('local')->put('verification/old.png', 'image');
        $user->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent", ['confirmation' => 'DELETE'])
            ->assertOk();

        $this->assertDatabaseMissing('provider_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        Storage::disk('local')->assertMissing('verification/old.png');
    }

    public function test_purge_requires_exact_delete_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $user->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent", ['confirmation' => 'delete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_file_cleanup_failure_is_reported_and_paths_remain_recoverable(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create(['avatar_path' => 'avatars/failed.png']);
        $user->delete();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with(['avatars/failed.png'])->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent", ['confirmation' => 'DELETE'])
            ->assertStatus(500);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $audit = DB::table('audit_logs')->where('action', 'user.permanently_deleted')->first();
        $this->assertSame(['avatars/failed.png'], json_decode($audit->metadata, true)['file_cleanup_paths']);
    }

    public function test_purge_removes_shared_client_history_but_preserves_unrelated_history(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);
        $booking = Booking::factory()->create(['client_id' => $user->id, 'provider_profile_id' => $profile->id, 'service_id' => $service->id]);
        $otherBooking = Booking::factory()->create(['client_id' => $otherClient->id, 'provider_profile_id' => $profile->id, 'service_id' => $service->id]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'client_id' => $user->id, 'provider_profile_id' => $profile->id]);
        $proof = PaymentProof::factory()->create(['payment_id' => $payment->id, 'uploaded_by' => $user->id, 'file_path' => 'payment-proofs/old.jpg']);
        Storage::disk('local')->put('payment-proofs/old.jpg', 'proof');
        $review = Review::factory()->create(['booking_id' => $booking->id, 'client_id' => $user->id, 'provider_profile_id' => $profile->id]);
        $conversation = Conversation::factory()->create(['client_id' => $user->id, 'provider_user_id' => $provider->id, 'booking_id' => $booking->id]);
        $message = Message::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $provider->id]);
        $otherConversation = Conversation::factory()->create(['client_id' => $otherClient->id, 'provider_user_id' => $provider->id]);
        $violation = ChatViolation::factory()->create(['user_id' => $user->id, 'conversation_id' => $conversation->id, 'message_id' => $message->id]);
        $report = Report::create(['reporter_id' => $provider->id, 'reportable_type' => Booking::class, 'reportable_id' => $booking->id, 'reason' => 'other', 'details' => 'Related booking report.']);
        AuditLogger::record($provider, 'booking.deleted', $booking);
        $provider->notify(new AccountEventNotification('payment.verified', "Booking {$booking->booking_code}", "/provider/bookings/{$booking->id}"));
        $removedNotificationId = $provider->notifications()->where('data->message', "Booking {$booking->booking_code}")->value('id');
        $provider->notify(new AccountEventNotification('payment.verified', "Booking {$otherBooking->booking_code}", "/provider/bookings/{$otherBooking->id}"));
        $provider->notify(new AccountEventNotification('report.received', "Booking {$booking->booking_code}", '/provider/reports'));
        $unrelatedNotificationId = $provider->notifications()->where('data->event', 'report.received')->value('id');
        DB::table('booking_status_histories')->insert(['booking_id' => $booking->id, 'to_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('suspension_appeals')->insert(['user_id' => $user->id, 'suspended_at' => now(), 'explanation' => 'Please restore my account.', 'created_at' => now(), 'updated_at' => now()]);
        $user->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent", ['confirmation' => 'DELETE'])
            ->assertOk();

        foreach (['users' => $user->id, 'bookings' => $booking->id, 'payments' => $payment->id, 'payment_proofs' => $proof->id, 'reviews' => $review->id, 'conversations' => $conversation->id, 'messages' => $message->id, 'chat_violations' => $violation->id, 'reports' => $report->id] as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }
        $this->assertDatabaseMissing('booking_status_histories', ['booking_id' => $booking->id]);
        $this->assertDatabaseMissing('suspension_appeals', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'booking.deleted', 'auditable_id' => $booking->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $removedNotificationId]);
        $this->assertDatabaseHas('notifications', ['id' => $unrelatedNotificationId]);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('users', ['id' => $provider->id]);
        $this->assertDatabaseHas('bookings', ['id' => $otherBooking->id]);
        $this->assertDatabaseHas('conversations', ['id' => $otherConversation->id]);
        Storage::disk('local')->assertMissing('payment-proofs/old.jpg');
    }

    public function test_purge_removes_provider_history_without_deleting_the_client(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->provider()->create();
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $service = Service::factory()->create(['provider_profile_id' => $profile->id]);
        $document = $profile->verificationDocuments()->create([
            'document_type' => 'government_id',
            'file_path' => 'verification/provider-id.png',
            'original_name' => 'provider-id.png',
            'mime_type' => 'image/png',
            'size_bytes' => 5,
        ]);
        Storage::disk('local')->put('verification/provider-id.png', 'image');
        AuditLogger::record($admin, 'verification.document_approved', $document);
        $booking = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id, 'service_id' => $service->id]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $proof = PaymentProof::factory()->create(['payment_id' => $payment->id, 'uploaded_by' => $client->id, 'file_path' => 'payment-proofs/provider-booking.jpg']);
        Storage::disk('local')->put('payment-proofs/provider-booking.jpg', 'proof');
        $review = Review::factory()->create(['booking_id' => $booking->id, 'client_id' => $client->id, 'provider_profile_id' => $profile->id]);
        $conversation = Conversation::factory()->create(['client_id' => $client->id, 'provider_user_id' => $provider->id]);
        Message::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $client->id]);
        $provider->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$provider->id}/permanent", ['confirmation' => 'DELETE'])
            ->assertOk();

        foreach (['users' => $provider->id, 'provider_profiles' => $profile->id, 'services' => $service->id, 'bookings' => $booking->id, 'payments' => $payment->id, 'payment_proofs' => $proof->id, 'reviews' => $review->id, 'conversations' => $conversation->id] as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }
        Storage::disk('local')->assertMissing('payment-proofs/provider-booking.jpg');
        Storage::disk('local')->assertMissing('verification/provider-id.png');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'verification.document_approved', 'auditable_id' => $document->id]);
        $this->assertDatabaseHas('users', ['id' => $client->id]);
    }

    public function test_purge_refuses_active_accounts_and_non_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
            ->assertStatus(409);

        $user->delete();
        $this->actingAs(User::factory()->client()->create())
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
            ->assertForbidden();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
