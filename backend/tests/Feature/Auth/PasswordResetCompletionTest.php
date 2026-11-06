<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetCodeNotification;
use App\Services\PasswordResetOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function requestCode(User $user): string
    {
        Notification::fake();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

        return Notification::sent($user, PasswordResetCodeNotification::class)[0]->code;
    }

    public function test_code_verification_grants_one_password_reset(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $grant = $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $code])
            ->assertOk()->json('data.grant');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'grant' => $grant,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        Notification::assertSentToTimes($user, PasswordChangedNotification::class, 1);
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'grant' => $grant,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ])->assertStatus(422);
        Notification::assertSentToTimes($user, PasswordChangedNotification::class, 1);
    }

    public function test_code_expires_after_ten_minutes(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->travel(601)->seconds();

        $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $code])->assertStatus(422);
    }

    public function test_rolled_back_password_change_does_not_send_confirmation(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;
        $code = $this->requestCode($user);
        $grant = $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $code])
            ->assertOk()->json('data.grant');

        DB::beginTransaction();
        try {
            app(PasswordResetOtpService::class)->reset($user->email, $grant, 'NewPassword123!');
            Notification::assertNotSentTo($user, PasswordChangedNotification::class);
        } finally {
            DB::rollBack();
        }

        $this->assertSame($originalHash, $user->fresh()->password);
        Notification::assertNotSentTo($user, PasswordChangedNotification::class);
    }

    public function test_five_wrong_codes_lock_challenge(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $wrongCode = $code === '000000' ? '999999' : '000000';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $wrongCode])->assertStatus(422);
        }

        $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $code])->assertStatus(422);
    }

    public function test_verified_grant_expires_after_ten_minutes(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $grant = $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $code])->json('data.grant');
        $this->travel(601)->seconds();

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'grant' => $grant,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422);
        Notification::assertNotSentTo($user, PasswordChangedNotification::class);
    }

    public function test_old_link_token_is_not_a_valid_reset_grant(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'old-link-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422);
    }

    public function test_grant_cannot_reset_a_new_account_reusing_the_same_email(): void
    {
        $original = User::factory()->create(['email' => 'reused@example.com']);
        $code = $this->requestCode($original);
        $grant = $this->postJson('/api/auth/verify-reset-code', [
            'email' => $original->email, 'code' => $code,
        ])->json('data.grant');

        $original->forceDelete();
        $replacement = User::factory()->create(['email' => 'reused@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'email' => $replacement->email,
            'grant' => $grant,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422);
        $this->assertFalse(Hash::check('NewPassword123!', $replacement->fresh()->password));
    }
}
