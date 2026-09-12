<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * One place wrapping the AuditLog::create() shape already used at the
 * original call site (BookingController::location(), Phase 5), so every
 * new admin action in Phase 9 logs the same way instead of hand-rolling
 * the same array repeatedly.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        User $actor,
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        $request ??= request();

        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
