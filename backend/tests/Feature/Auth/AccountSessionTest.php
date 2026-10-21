<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_idle_session_is_rejected_without_user_activity(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($user)
            ->withSession(['webis.last_user_activity' => now()->subMinutes(31)->timestamp])
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('errors.session.0', 'expired');
    }

    public function test_background_reads_do_not_extend_the_idle_clock(): void
    {
        $user = User::factory()->client()->create();
        $started = now()->subMinutes(29)->timestamp;

        $this->actingAs($user)
            ->withSession(['webis.last_user_activity' => $started])
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->assertSame($started, session('webis.last_user_activity'));
    }

    public function test_an_older_session_is_rejected_after_a_new_login(): void
    {
        $user = User::factory()->client()->create(['active_session_id' => 'newer-device-session']);

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('errors.session.0', 'replaced');
    }

    public function test_terminated_account_cannot_sign_in_or_use_a_remaining_session(): void
    {
        $user = User::factory()->client()->create([
            'password' => 'Password123',
            'terminated_at' => now(),
        ]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertForbidden();

        $this->actingAs($user)->getJson('/api/auth/me')->assertForbidden();
    }
}
