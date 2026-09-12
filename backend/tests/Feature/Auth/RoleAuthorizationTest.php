<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the coarse role gate. Every role must be refused on the other two
 * role groups, and guests must be refused everywhere.
 *
 * This is the Phase 1 exit criterion for role-based authorization. As real
 * endpoints land in later phases they are added to the matrix below.
 */
class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0:string, 1:string, 2:int}>
     */
    public static function accessMatrix(): array
    {
        return [
            // role      endpoint            expected
            'client on client area' => ['client', '/api/client/ping', 200],
            'client on provider area' => ['client', '/api/provider/ping', 403],
            'client on admin area' => ['client', '/api/admin/ping', 403],

            'provider on client area' => ['provider', '/api/client/ping', 403],
            'provider on provider area' => ['provider', '/api/provider/ping', 200],
            'provider on admin area' => ['provider', '/api/admin/ping', 403],

            'admin on client area' => ['admin', '/api/client/ping', 403],
            'admin on provider area' => ['admin', '/api/provider/ping', 403],
            'admin on admin area' => ['admin', '/api/admin/ping', 200],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('accessMatrix')]
    public function test_role_groups_are_enforced(string $role, string $endpoint, int $expected): void
    {
        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)
            ->getJson($endpoint)
            ->assertStatus($expected);
    }

    public function test_guests_are_refused_on_every_protected_group(): void
    {
        foreach (['/api/client/ping', '/api/provider/ping', '/api/admin/ping'] as $endpoint) {
            $this->getJson($endpoint)
                ->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    public function test_a_suspended_user_is_refused_even_with_the_right_role(): void
    {
        $user = User::factory()->admin()->suspended()->create();

        $this->actingAs($user)
            ->getJson('/api/admin/ping')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has been suspended.');
    }

    public function test_a_user_cannot_change_their_own_role_through_mass_assignment(): void
    {
        $user = User::factory()->client()->create();

        // Simulates a controller that carelessly fills request input.
        //
        // Two safe outcomes are possible, and both are asserted here:
        //  - outside production Model::shouldBeStrict() is on, so filling a
        //    non-fillable attribute THROWS (loud failure, caught in development);
        //  - in production the attribute is silently discarded (quiet failure).
        // Either way `role` must never change.
        try {
            $user->fill(['role' => 'admin']);
            $user->save();
        } catch (MassAssignmentException) {
            // Expected under strict mode. The assignment was refused.
        }

        $this->assertSame('client', $user->fresh()->role->value);
    }
}
