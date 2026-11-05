<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BootstrapSuperAdmin extends Command
{
    protected $signature = 'user:bootstrap-super-admin';

    protected $description = 'Create the first Super Admin using temporary deployment secrets';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) env('WEBIS_BOOTSTRAP_SUPER_ADMIN_EMAIL')));
        $password = (string) env('WEBIS_BOOTSTRAP_SUPER_ADMIN_PASSWORD');

        if ($email === '' && $password === '') {
            return self::SUCCESS;
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Bootstrap email is missing or invalid.');

            return self::FAILURE;
        }

        if (strlen($password) < 16) {
            $this->error('Bootstrap password is missing or shorter than 16 characters.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($email, $password): int {
            if (User::query()->where('role', UserRole::SuperAdmin->value)->exists()) {
                $this->info('A Super Admin already exists; no account changed.');

                return self::SUCCESS;
            }

            if (User::withTrashed()->where('email', $email)->exists()) {
                $this->error('Email already belongs to an account; no account changed.');

                return self::FAILURE;
            }

            $user = new User;
            $user->fill([
                'first_name' => 'System',
                'last_name' => 'Super Administrator',
                'email' => $email,
                'password' => $password,
            ]);
            $user->role = UserRole::SuperAdmin;
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->save();

            AuditLog::create([
                'actor_id' => null,
                'action' => 'user.super_admin_bootstrapped',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => ['email' => $email, 'source' => 'deployment_bootstrap'],
            ]);

            $this->info('First Super Admin created. Remove the bootstrap environment variables now.');

            return self::SUCCESS;
        });
    }
}
