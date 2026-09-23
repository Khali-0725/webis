<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'test-client-id.apps.googleusercontent.com']);
    }

    /**
     * Fakes what Google's tokeninfo endpoint returns for a valid credential.
     * The real endpoint has already checked the signature/issuer/expiry by
     * the time it answers 200 - GoogleAuthService only re-checks `aud` and
     * `email_verified` itself.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function fakeGoogleToken(array $overrides = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(array_merge([
                'aud' => 'test-client-id.apps.googleusercontent.com',
                'sub' => '110169484474386276334',
                'email' => 'juan@example.com',
                'email_verified' => 'true',
                'given_name' => 'Juan',
                'family_name' => 'Dela Cruz',
            ], $overrides)),
        ]);
    }

    public function test_a_new_client_account_is_created_and_signed_in(): void
    {
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'client'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'juan@example.com')
            ->assertJsonPath('data.role', 'client')
            ->assertJsonPath('data.email_verified', true);

        $user = User::where('email', 'juan@example.com')->firstOrFail();

        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame('110169484474386276334', $user->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_new_provider_account_gets_an_empty_provider_profile(): void
    {
        $this->fakeGoogleToken(['email' => 'plumber@example.com']);

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'provider'])
            ->assertCreated()
            ->assertJsonPath('data.role', 'provider');

        $user = User::where('email', 'plumber@example.com')->firstOrFail();

        $this->assertNotNull($user->providerProfile);
    }

    public function test_login_page_flow_signs_in_an_existing_account_with_no_role_sent(): void
    {
        $existing = User::factory()->create([
            'email' => 'juan@example.com',
            'google_id' => '110169484474386276334',
        ]);
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $existing->id);

        $this->assertAuthenticatedAs($existing);
    }

    public function test_login_page_flow_is_refused_when_no_account_exists_yet(): void
    {
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_it_links_google_onto_a_pre_existing_password_account_with_the_same_email(): void
    {
        $existing = User::factory()->create(['email' => 'juan@example.com', 'google_id' => null]);
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'client'])
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertSame('110169484474386276334', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'juan@example.com')->count());
    }

    public function test_a_suspended_account_cannot_sign_in_via_google(): void
    {
        User::factory()->suspended()->create([
            'email' => 'banned@example.com',
            'google_id' => '110169484474386276334',
        ]);
        $this->fakeGoogleToken(['email' => 'banned@example.com']);

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt'])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_a_deleted_accounts_email_is_refused_cleanly_instead_of_500ing(): void
    {
        // Soft-deleted rows still hold the UNIQUE email/google_id, so creating
        // a fresh account over them would hit the DB constraint.
        User::factory()->create([
            'email' => 'juan@example.com',
            'google_id' => '110169484474386276334',
        ])->delete();
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'client'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertGuest();
        $this->assertSame(1, User::withTrashed()->where('email', 'juan@example.com')->count());
    }

    public function test_a_token_minted_for_a_different_google_client_is_rejected(): void
    {
        $this->fakeGoogleToken(['aud' => 'someone-elses-client-id.apps.googleusercontent.com']);

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'client'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_an_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogleToken(['email_verified' => 'false']);

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'client'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_a_token_google_rejects_outright_is_rejected(): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_token'], 400)]);

        $this->postJson('/api/auth/google', ['credential' => 'garbage', 'role' => 'client'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertGuest();
    }

    public function test_credential_is_required(): void
    {
        $this->postJson('/api/auth/google', ['role' => 'client'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('credential');
    }

    public function test_a_visitor_cannot_self_create_an_admin_account_via_google(): void
    {
        $this->fakeGoogleToken();

        $this->postJson('/api/auth/google', ['credential' => 'fake-jwt', 'role' => 'admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_a_signed_in_user_cannot_call_this_endpoint(): void
    {
        $this->fakeGoogleToken();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/auth/google', ['credential' => 'fake-jwt'])
            ->assertForbidden();
    }
}
