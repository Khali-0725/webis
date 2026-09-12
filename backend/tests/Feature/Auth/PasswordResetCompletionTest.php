<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_token_resets_the_password_and_the_new_password_signs_in(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password has been reset.');

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'NewPassword123!',
        ])->assertOk();
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422);
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'SomethingElse123!',
        ])->assertStatus(422);
    }
}
