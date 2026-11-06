<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use RuntimeException;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_account_gets_six_digit_code_and_unknown_account_gets_same_response(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()->assertJsonPath('message', 'If an account exists, a reset code has been sent.');
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()->assertJsonPath('message', 'If an account exists, a reset code has been sent.');

        Notification::assertSentTo($user, PasswordResetCodeNotification::class, fn ($notice) => preg_match('/^\d{6}$/', $notice->code) === 1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'missing@example.com']);
    }

    public function test_resend_waits_sixty_seconds_and_invalidates_previous_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        $first = Notification::sent($user, PasswordResetCodeNotification::class)[0]->code;

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentToTimes($user, PasswordResetCodeNotification::class, 1);

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentToTimes($user, PasswordResetCodeNotification::class, 2);
        $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $first])->assertStatus(422);
    }

    public function test_deleted_account_does_not_receive_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->delete();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertNothingSent();
    }

    public function test_mail_failure_does_not_leave_a_usable_code(): void
    {
        $user = User::factory()->create();
        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('Mail unavailable'));
        app()->instance(Dispatcher::class, $dispatcher);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        $this->assertDatabaseMissing('password_reset_challenges', ['email' => $user->email]);
    }
}
