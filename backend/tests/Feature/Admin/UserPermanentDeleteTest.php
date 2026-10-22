<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
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
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
            ->assertOk();

        $this->assertDatabaseMissing('provider_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        Storage::disk('local')->assertMissing('verification/old.png');
    }

    public function test_purge_refuses_an_account_with_booking_history(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();
        $booking = Booking::factory()->create(['client_id' => $user->id]);
        $user->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$user->id}/permanent")
            ->assertStatus(409);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
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
