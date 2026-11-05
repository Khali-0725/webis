<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_named_existing_admin_is_promoted_with_explicit_confirmation(): void
    {
        $target = User::factory()->admin()->create(['email' => 'superadmin@webis.test']);
        $other = User::factory()->admin()->create();

        $this->artisan('user:promote-super-admin', [
            'email' => 'superadmin@webis.test',
            '--confirm-email' => 'superadmin@webis.test',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'super_admin']);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'role' => 'admin']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.super_admin_promoted', 'auditable_id' => $target->id]);
    }

    public function test_missing_or_mismatched_confirmation_does_not_promote(): void
    {
        $target = User::factory()->admin()->create(['email' => 'superadmin@webis.test']);

        $this->artisan('user:promote-super-admin', ['email' => $target->email])->assertExitCode(1);
        $this->artisan('user:promote-super-admin', [
            'email' => $target->email,
            '--confirm-email' => 'someone@example.com',
        ])->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'admin']);
    }

    public function test_missing_non_admin_and_deleted_accounts_are_refused(): void
    {
        $client = User::factory()->client()->create();
        $deleted = User::factory()->admin()->create();
        $deleted->delete();

        foreach (['missing@example.com', $client->email, $deleted->email] as $email) {
            $this->artisan('user:promote-super-admin', [
                'email' => $email,
                '--confirm-email' => $email,
            ])->assertExitCode(1);
        }

        $this->assertDatabaseMissing('users', ['role' => 'super_admin']);
    }
}
