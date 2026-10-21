<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class AccountSuspensionService
{
    public static function maximumDays(): int
    {
        return min(15, max(1, (int) config('webis.suspension.max_days', 15)));
    }

    /** @param array{reason: string, basis: string, days: int} $data */
    public function suspend(User $user, User $admin, array $data, ?Request $request = null, ?string $source = null): void
    {
        if ($user->id === $admin->id || $user->terminated_at !== null) {
            throw DomainException::forbidden('This account cannot be suspended.');
        }
        if ($data['days'] < 1 || $data['days'] > self::maximumDays()) {
            throw DomainException::unprocessable('The suspension duration exceeds the current policy.');
        }

        $startsAt = now();
        $endsAt = $startsAt->copy()->addDays($data['days']);
        $user->forceFill([
            'status' => UserStatus::Suspended,
            'suspended_at' => $startsAt,
            'suspended_until' => $endsAt,
            'suspension_reason' => $data['reason'],
            'suspension_basis' => $data['basis'],
            'suspended_by' => $admin->id,
            'active_session_id' => null,
        ])->save();

        AuditLogger::record($admin, 'user.suspended', $user, [
            ...$data,
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            'source' => $source,
        ], $request);
    }
}
