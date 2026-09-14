<?php

namespace Tests\Feature\Admin;

use App\Models\Barangay;
use App\Models\Booking;
use App\Models\ChatViolation;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full CRUD + soft delete across every admin-managed resource. Each delete
 * asserts the row is *soft* deleted (still in the table with deleted_at),
 * that it leaves the default listing, that ?trashed=only shows it, and
 * that restore brings it back.
 */
class AdminCrudSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ------------------------------------------------------------ Users

    public function test_admin_can_create_a_user_of_any_role(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson('/api/admin/users', [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'Maria.Santos@Example.com',
                'role' => 'provider',
                'password' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'maria.santos@example.com')
            ->assertJsonPath('data.role', 'provider');

        $user = User::find($response->json('data.id'));
        $this->assertNotNull($user->providerProfile, 'an admin-created provider gets a profile like self-registration');
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_update_a_user(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$user->id}", ['first_name' => 'Renamed', 'phone' => '0917 123 4567'])
            ->assertOk()
            ->assertJsonPath('data.full_name', "Renamed {$user->last_name}")
            ->assertJsonPath('data.phone', '09171234567');
    }

    public function test_admin_soft_deletes_and_restores_a_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->client()->create();

        $this->actingAs($admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted', 'auditable_id' => $user->id]);

        $this->actingAs($admin)->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonMissing(['id' => $user->id]);

        $this->actingAs($admin)->getJson('/api/admin/users?trashed=only')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id]);

        $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted_at', null);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_a_soft_deleted_user_can_no_longer_sign_in(): void
    {
        $user = User::factory()->client()->create(['password' => 'password123']);
        $user->delete();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertStatus(422);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->deleteJson("/api/admin/users/{$admin->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_use_user_crud(): void
    {
        $client = User::factory()->client()->create();
        $other = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/admin/users', [])->assertForbidden();
        $this->actingAs($client)->patchJson("/api/admin/users/{$other->id}", ['first_name' => 'X'])->assertForbidden();
        $this->actingAs($client)->deleteJson("/api/admin/users/{$other->id}")->assertForbidden();
    }

    // ------------------------------------------------------------ Categories

    public function test_admin_soft_deletes_and_restores_a_category(): void
    {
        $admin = $this->admin();
        $category = ServiceCategory::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/admin/categories/{$category->id}")->assertOk();
        $this->assertSoftDeleted('service_categories', ['id' => $category->id]);

        $this->actingAs($admin)->getJson('/api/admin/categories')->assertJsonMissing(['id' => $category->id]);
        $this->actingAs($admin)->getJson('/api/admin/categories?trashed=only')->assertJsonFragment(['id' => $category->id]);
        $this->getJson('/api/service-categories')->assertJsonMissing(['id' => $category->id]);

        $this->actingAs($admin)->postJson("/api/admin/categories/{$category->id}/restore")->assertOk();
        $this->assertDatabaseHas('service_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_a_category_with_services_cannot_be_deleted(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/categories/{$service->service_category_id}")
            ->assertStatus(409);
    }

    // ------------------------------------------------------------ Barangays

    public function test_admin_soft_deletes_and_restores_an_unused_barangay(): void
    {
        $admin = $this->admin();
        $barangay = Barangay::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/admin/barangays/{$barangay->id}")->assertOk();
        $this->assertSoftDeleted('barangays', ['id' => $barangay->id]);

        $this->actingAs($admin)->postJson("/api/admin/barangays/{$barangay->id}/restore")->assertOk();
        $this->assertDatabaseHas('barangays', ['id' => $barangay->id, 'deleted_at' => null]);
    }

    public function test_a_barangay_in_use_cannot_be_deleted(): void
    {
        $barangay = Barangay::factory()->create();
        User::factory()->client()->create(['barangay_id' => $barangay->id]);

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/barangays/{$barangay->id}")
            ->assertStatus(409);
    }

    // ------------------------------------------------------------ Services

    public function test_admin_can_update_soft_delete_and_restore_a_service(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/services/{$service->id}", ['title' => 'Moderated title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Moderated title');

        $this->actingAs($admin)->deleteJson("/api/admin/services/{$service->id}")->assertOk();
        $this->assertSoftDeleted('services', ['id' => $service->id]);

        $this->actingAs($admin)->getJson('/api/admin/services?trashed=only')->assertJsonFragment(['id' => $service->id]);

        $this->actingAs($admin)->postJson("/api/admin/services/{$service->id}/restore")->assertOk();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
    }

    public function test_a_booking_still_shows_its_service_after_the_service_is_deleted(): void
    {
        $booking = Booking::factory()->create();
        $title = $booking->service->title;

        $booking->service->delete();

        $this->actingAs($this->admin())
            ->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.service.title', $title);
    }

    // ------------------------------------------------------------ Providers

    public function test_admin_can_update_soft_delete_and_restore_a_provider_profile(): void
    {
        $admin = $this->admin();
        $profile = ProviderProfile::factory()->verified()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/providers/{$profile->id}", ['business_name' => 'Fixed Name'])
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Fixed Name');

        $this->actingAs($admin)->deleteJson("/api/admin/providers/{$profile->id}")->assertOk();
        $this->assertSoftDeleted('provider_profiles', ['id' => $profile->id]);
        $this->getJson('/api/providers')->assertJsonMissing(['id' => $profile->id]);

        $this->actingAs($admin)->postJson("/api/admin/providers/{$profile->id}/restore")->assertOk();
        $this->assertDatabaseHas('provider_profiles', ['id' => $profile->id, 'deleted_at' => null]);
    }

    // ------------------------------------------------------------ Bookings

    public function test_admin_soft_deletes_and_restores_a_booking(): void
    {
        $admin = $this->admin();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/bookings/{$booking->id}")->assertOk();
        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.deleted', 'auditable_id' => $booking->id]);

        $this->actingAs($admin)->getJson('/api/bookings?trashed=only')->assertJsonFragment(['id' => $booking->id]);

        $this->actingAs($admin)->postJson("/api/bookings/{$booking->id}/restore")->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'deleted_at' => null]);
    }

    // ------------------------------------------------------------ Payments

    public function test_admin_soft_deletes_and_restores_a_payment_but_parties_cannot(): void
    {
        $admin = $this->admin();
        $client = User::factory()->client()->create();
        $payment = Payment::factory()->create(['client_id' => $client->id]);

        $this->actingAs($client)->deleteJson("/api/payments/{$payment->id}")->assertForbidden();

        $this->actingAs($admin)->deleteJson("/api/payments/{$payment->id}")->assertOk();
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);

        $this->actingAs($admin)->postJson("/api/payments/{$payment->id}/restore")->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'deleted_at' => null]);
    }

    // ------------------------------------------------------------ Reports + violations

    public function test_admin_soft_deletes_and_restores_a_report(): void
    {
        $admin = $this->admin();
        $report = Report::create([
            'reporter_id' => User::factory()->client()->create()->id,
            'reportable_type' => User::class,
            'reportable_id' => User::factory()->provider()->create()->id,
            'reason' => 'other',
            'details' => 'x',
        ]);

        $this->actingAs($admin)->deleteJson("/api/admin/reports/{$report->id}")->assertOk();
        $this->assertSoftDeleted('reports', ['id' => $report->id]);

        $this->actingAs($admin)->getJson('/api/admin/reports?trashed=only')->assertJsonFragment(['id' => $report->id]);

        $this->actingAs($admin)->postJson("/api/admin/reports/{$report->id}/restore")->assertOk();
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'deleted_at' => null]);
    }

    public function test_admin_soft_deletes_and_restores_a_chat_violation(): void
    {
        $admin = $this->admin();
        $violation = ChatViolation::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/admin/chat-violations/{$violation->id}")->assertOk();
        $this->assertSoftDeleted('chat_violations', ['id' => $violation->id]);

        $this->actingAs($admin)->postJson("/api/admin/chat-violations/{$violation->id}/restore")->assertOk();
        $this->assertDatabaseHas('chat_violations', ['id' => $violation->id, 'deleted_at' => null]);
    }

    public function test_restoring_a_live_record_is_a_conflict(): void
    {
        $category = ServiceCategory::factory()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/categories/{$category->id}/restore")
            ->assertStatus(409);
    }
}
