<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_without_an_address_can_load_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.address_line', null);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/me/profile', [
                'first_name' => 'New',
                'last_name' => 'Name',
                'phone' => '09171234567',
                'address_line' => '12 Rizal Street',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'New')
            ->assertJsonPath('data.phone', '09171234567')
            ->assertJsonPath('data.address_line', '12 Rizal Street');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'address_line' => '12 Rizal Street',
            'phone' => '09171234567',
        ]);
    }

    public function test_changing_email_requires_new_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->patchJson('/api/me/profile', ['email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.email_verified', false);

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_updating_other_details_keeps_email_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->patchJson('/api/me/profile', ['address_line' => '18 Mabini Street'])
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);

        Notification::assertNothingSent();
    }

    public function test_duplicate_email_is_rejected_without_changing_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/me/profile', [
                'email' => $other->email,
                'address_line' => 'Should not persist',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertNull($user->fresh()->address_line);
    }
}
