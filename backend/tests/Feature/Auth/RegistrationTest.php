<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'role' => 'client',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'accepted_terms' => true,
        ], $overrides);
    }

    public function test_a_visitor_can_register_as_a_client(): void
    {
        $this->postJson('/api/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'client')
            ->assertJsonPath('data.home_path', '/client/dashboard');

        $user = User::where('email', 'juan@example.com')->firstOrFail();

        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_a_visitor_can_register_as_a_provider(): void
    {
        $this->postJson('/api/auth/register', $this->payload([
            'email' => 'plumber@example.com',
            'role' => 'provider',
        ]))->assertCreated()
            ->assertJsonPath('data.role', 'provider');
    }

    public function test_passwords_are_hashed_and_never_returned(): void
    {
        $response = $this->postJson('/api/auth/register', $this->payload());

        $user = User::where('email', 'juan@example.com')->firstOrFail();

        $this->assertNotSame('Password123', $user->password);
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_a_visitor_cannot_register_as_an_admin(): void
    {
        $this->postJson('/api/auth/register', $this->payload([
            'email' => 'attacker@example.com',
            'role' => 'admin',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.com']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', $this->payload(['email' => 'taken@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->postJson('/api/auth/register', $this->payload([
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_registration_rejects_a_mismatched_confirmation(): void
    {
        $this->postJson('/api/auth/register', $this->payload([
            'password_confirmation' => 'DifferentPassword1',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_registration_rejects_an_invalid_philippine_mobile_number(): void
    {
        $this->postJson('/api/auth/register', $this->payload(['phone' => '12345']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->postJson('/api/auth/register', $this->payload(['accepted_terms' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('accepted_terms');
    }

    public function test_a_signed_in_user_cannot_register_another_account(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/auth/register', $this->payload(['email' => 'second@example.com']))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }
}
