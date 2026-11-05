<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

class BootstrapSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Env::getRepository()->clear('WEBIS_BOOTSTRAP_SUPER_ADMIN_EMAIL');
        Env::getRepository()->clear('WEBIS_BOOTSTRAP_SUPER_ADMIN_PASSWORD');
        parent::tearDown();
    }

    public function test_bootstrap_creates_only_one_super_admin_and_does_not_change_existing_password(): void
    {
        Env::getRepository()->set('WEBIS_BOOTSTRAP_SUPER_ADMIN_EMAIL', 'superadmin@webis.test');
        Env::getRepository()->set('WEBIS_BOOTSTRAP_SUPER_ADMIN_PASSWORD', 'a-long-private-password-123');

        $this->artisan('user:bootstrap-super-admin')->assertSuccessful();
        $user = User::query()->where('email', 'superadmin@webis.test')->firstOrFail();
        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertNotSame('a-long-private-password-123', $user->password);

        Env::getRepository()->set('WEBIS_BOOTSTRAP_SUPER_ADMIN_PASSWORD', 'another-long-password-456');
        $this->artisan('user:bootstrap-super-admin')->assertSuccessful();
        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame(1, User::query()->where('role', UserRole::SuperAdmin->value)->count());
    }

    public function test_bootstrap_refuses_to_upgrade_an_existing_account(): void
    {
        User::factory()->create(['email' => 'superadmin@webis.test', 'role' => UserRole::Client]);
        Env::getRepository()->set('WEBIS_BOOTSTRAP_SUPER_ADMIN_EMAIL', 'superadmin@webis.test');
        Env::getRepository()->set('WEBIS_BOOTSTRAP_SUPER_ADMIN_PASSWORD', 'a-long-private-password-123');

        $this->artisan('user:bootstrap-super-admin')->assertFailed();
        $this->assertSame(UserRole::Client, User::query()->where('email', 'superadmin@webis.test')->firstOrFail()->role);
    }
}
