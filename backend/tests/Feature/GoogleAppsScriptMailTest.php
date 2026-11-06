<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAppsScriptMailTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://script.google.com/macros/s/test-deployment/exec';

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config([
            'mail.default' => 'google_apps_script',
            'mail.mailers.google_apps_script.endpoint' => self::URL,
            'mail.mailers.google_apps_script.secret' => str_repeat('a', 64),
            'webis.frontend_url' => 'https://webis.example',
        ]);
        Http::preventStrayRequests();
    }

    public function test_reset_code_is_sent_over_https_and_can_complete_password_reset(): void
    {
        Http::fake([self::URL => Http::response(['ok' => true])]);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

        $request = Http::recorded()[0][0];
        $payload = json_decode($request['payload'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($user->email, $payload['to']);
        $this->assertSame('Your WEBIS password reset code', $payload['subject']);
        $this->assertStringContainsString('expires in 10 minutes', $payload['text']);
        $this->assertStringContainsString('<html', $payload['html']);
        $this->assertSame(hash_hmac('sha256', $request['payload'], str_repeat('a', 64)), $request['signature']);
        $this->assertSame(now()->timestamp, $payload['timestamp']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $payload['id']);
        $this->assertArrayNotHasKey('secret', $request->data());
        preg_match('/\b\d{6}\b/', $payload['text'], $match);
        $this->assertNotEmpty($match);

        $grant = $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $match[0]])
            ->assertOk()->json('data.grant');
        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email, 'grant' => $grant,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertOk();
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        Http::assertSentCount(2);
        $confirmation = Http::recorded()[1][0];
        $confirmationPayload = json_decode($confirmation['payload'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($user->email, $confirmationPayload['to']);
        $this->assertSame('Your WEBIS password was changed', $confirmationPayload['subject']);
        $this->assertStringContainsString('changed successfully', $confirmationPayload['text']);
        $this->assertStringContainsString('If you did not make this change', $confirmationPayload['text']);
        $this->assertStringContainsString('https://webis.example/login', $confirmationPayload['html']);
        $this->assertStringNotContainsString('NewPassword123!', $confirmation['payload']);
        $this->assertStringNotContainsString($match[0], $confirmationPayload['text']);
        $this->assertSame(hash_hmac('sha256', $confirmation['payload'], str_repeat('a', 64)), $confirmation['signature']);
    }

    public function test_confirmation_delivery_failure_does_not_undo_a_successful_password_reset(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push(['ok' => true])
            ->push(['ok' => false, 'error' => 'quota_exceeded'])]);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        $payload = json_decode(Http::recorded()[0][0]['payload'], true, flags: JSON_THROW_ON_ERROR);
        preg_match('/\b\d{6}\b/', $payload['text'], $match);
        $grant = $this->postJson('/api/auth/verify-reset-code', ['email' => $user->email, 'code' => $match[0]])
            ->assertOk()->json('data.grant');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email, 'grant' => $grant,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_challenges', ['email' => $user->email]);
        Http::assertSentCount(2);
    }

    public function test_google_rejection_returns_503_and_rolls_back_the_challenge(): void
    {
        Http::fake([self::URL => Http::response(['ok' => false, 'error' => 'quota_exceeded'])]);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        $this->assertDatabaseMissing('password_reset_challenges', ['email' => $user->email]);
        Http::assertSentCount(1);
    }

    public function test_google_sign_in_page_is_not_treated_as_a_sent_email(): void
    {
        Http::fake([self::URL => Http::response('<html>Sign in</html>', 200)]);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        $this->assertDatabaseMissing('password_reset_challenges', ['email' => $user->email]);
    }

    public function test_http_failure_is_not_retried_or_treated_as_sent(): void
    {
        Http::fake([self::URL => Http::response('Unavailable', 503)]);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        $this->assertDatabaseMissing('password_reset_challenges', ['email' => $user->email]);
        Http::assertSentCount(1);
    }

    public function test_missing_secret_fails_before_any_network_request(): void
    {
        config(['mail.mailers.google_apps_script.secret' => '']);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_invalid_endpoint_fails_before_any_network_request(): void
    {
        config(['mail.mailers.google_apps_script.endpoint' => 'https://example.com/exec']);
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_unknown_account_does_not_send_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.com'])->assertOk();
        Http::assertNothingSent();
    }
}
