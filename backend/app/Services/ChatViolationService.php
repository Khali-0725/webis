<?php

namespace App\Services;

use App\Enums\ViolationAdminStatus;
use App\Exceptions\DomainException;
use App\Models\ChatViolation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin disposition of a flagged message. Suspensions use the same account
 * policy and audit trail as the user-management action.
 */
class ChatViolationService
{
    public function __construct(private readonly AccountSuspensionService $suspensions) {}

    public function action(ChatViolation $violation, User $admin, string $action, array $suspension = [], ?Request $request = null): ChatViolation
    {
        return DB::transaction(function () use ($violation, $admin, $action, $suspension, $request) {
            $violation = ChatViolation::query()->lockForUpdate()->findOrFail($violation->id);
            if (! in_array($violation->admin_status, [ViolationAdminStatus::Open, ViolationAdminStatus::Reviewed], true)) {
                throw DomainException::conflict('This moderation case has already been closed.');
            }
            $adminStatus = match ($action) {
                'dismiss' => ViolationAdminStatus::Dismissed,
                'warn', 'suspend' => ViolationAdminStatus::Actioned,
                default => throw DomainException::unprocessable('Unknown violation action.'),
            };

            if ($action === 'suspend' && ! $violation->user) {
                throw DomainException::conflict('The account for this case is unavailable.');
            }

            $violation->forceFill([
                'admin_status' => $adminStatus,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            if ($action === 'suspend') {
                $this->suspensions->suspend(
                    $violation->user, $admin, $suspension, $request, 'chat_violation:'.$violation->id,
                );
            }

            return $violation->fresh();
        });
    }
}
