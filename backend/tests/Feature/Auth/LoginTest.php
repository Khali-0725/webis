<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'client@webis.test',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    public function test_a_user_can_sign_in_with_correct_credentials(): void
    {
        $user = $this->user();

        $this->postJson('/api/auth/login', [
            'email' => 'client@webis.test',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'client');

        $this->assertAuthenticatedAs($user);
    }

    public function test_sign_in_records_the_last_login_timestamp(): void
    {
        $user = $this->user();

        $this->assertNull($user->last_login_at);

        $this->postJson('/api/auth/login', [
            'email' => 'client@webis.test',
            'password' => 'Password123',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_sign_in_fails_with_a_wrong_password(): void
    {
        $this->user();

        $this->postJson('/api/auth/login', [
            'email' => 'client@webis.test',
            'password' => 'WrongPassword1',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_the_error_message_does_not_reveal_whether_the_email_exists(): void
    {
        $this->user();

        $known = $this->postJson('/api/auth/login', [
            'email' => 'client@webis.test',
            'password' => 'WrongPassword1',
        ]);

        $unknown = $this->postJson('/api/auth/login', [
            'email' => 'nobody@webis.test',
            'password' => 'WrongPassword1',
        ]);

        $this->assertSame($known->json('message'), $unknown->json('message'));
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        $this->user();
        $suspended = User::factory()->suspended()->create([
            'email' => 'banned@webis.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'banned@webis.test',
            'password' => 'Password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertGuest();
        $this->assertNotNull($suspended->fresh());
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'client@webis.test',
                'password' => 'WrongPassword1',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'client@webis.test',
            'password' => 'Password123',
        ])->assertStatus(429);
    }

    public function test_me_returns_the_signed_in_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'client@webis.test');
    }

    public function test_me_is_rejected_for_a_guest(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_a_user_can_sign_out(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_the_user_payload_never_contains_the_password_hash(): void
    {
        $user = $this->user();

        $body = $this->actingAs($user)->getJson('/api/auth/me')->json('data');

        $this->assertArrayNotHasKey('password', $body);
        $this->assertArrayNotHasKey('remember_token', $body);
    }
}
