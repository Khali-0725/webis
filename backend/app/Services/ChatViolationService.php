<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Enums\ViolationAdminStatus;
use App\Exceptions\DomainException;
use App\Models\ChatViolation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin disposition of a flagged message (§9.3 / Phase 9). `warn`/`suspend`/
 * `dismiss` don't map 1:1 onto ViolationAdminStatus's 4 cases (there's no
 * "Warned" case) - `dismiss` -> Dismissed, `warn` and `suspend` both ->
 * Actioned (the only "something was done" case left), and `suspend`
 * additionally suspends the offending account. `warn` has no account
 * side-effect - there's no in-app notification system yet to deliver it
 * through, so it's logged only.
 */
class ChatViolationService
{
    public function action(ChatViolation $violation, User $admin, string $action): ChatViolation
    {
        return DB::transaction(function () use ($violation, $admin, $action) {
            $adminStatus = match ($action) {
                'dismiss' => ViolationAdminStatus::Dismissed,
                'warn', 'suspend' => ViolationAdminStatus::Actioned,
                default => throw DomainException::unprocessable('Unknown violation action.'),
            };

            $violation->forceFill([
                'admin_status' => $adminStatus,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            if ($action === 'suspend' && $violation->user) {
                $violation->user->forceFill(['status' => UserStatus::Suspended])->save();
            }

            return $violation->fresh();
        });
    }
}
