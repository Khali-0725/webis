<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PromoteSuperAdmin extends Command
{
    protected $signature = 'user:promote-super-admin {email : Existing Admin account email} {--confirm-email= : Repeat the exact email to confirm}';

    protected $description = 'Explicitly promote one existing active Admin account to Super Admin';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $confirmation = mb_strtolower(trim((string) $this->option('confirm-email')));

        if ($email === '' || $confirmation !== $email) {
            $this->error('Repeat the account email with --confirm-email to confirm promotion.');

            return self::FAILURE;
        }

        $promoted = DB::transaction(function () use ($email): bool {
            $user = User::query()->where('email', $email)->lockForUpdate()->first();

            if (! $user || $user->role !== UserRole::Admin || $user->status !== UserStatus::Active || $user->terminated_at !== null) {
                return false;
            }

            $user->forceFill(['role' => UserRole::SuperAdmin])->save();

            AuditLog::create([
                'actor_id' => null,
                'action' => 'user.super_admin_promoted',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => ['email' => $email, 'source' => 'operator_command'],
            ]);

            return true;
        });

        if (! $promoted) {
            $this->error('No active Admin account with that email was found. No account was changed.');

            return self::FAILURE;
        }

        $this->info("Promoted {$email} to Super Admin.");

        return self::SUCCESS;
    }
}
