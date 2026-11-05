<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;
use RuntimeException;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists, a reset link has been sent.');
    }

    public function test_delivery_failure_is_not_reported_as_sent(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andThrow(new RuntimeException('Mail provider rejected the request'));

        $this->postJson('/api/auth/forgot-password', ['email' => 'user@example.com'])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Reset email is temporarily unavailable. Please try again later.');
    }

    public function test_unknown_email_keeps_generic_response(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists, a reset link has been sent.');
    }
}
