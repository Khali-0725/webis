<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role gate. Usage: ->middleware('role:admin') or 'role:client,provider'.
 *
 * This is the coarse gate that keeps whole route groups apart. Record-level
 * ownership is still enforced by Policies - never rely on this alone.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('You must be signed in to do that.', [], 401);
        }

        if (! $user->canAccessAccount()) {
            return ApiResponse::error('This account is suspended or terminated.', [], 403);
        }

        if (! in_array($user->role->value, $roles, true)) {
            return ApiResponse::error('You are not allowed to perform this action.', [], 403);
        }

        // Read the current database state, not an already-loaded relation from
        // an earlier request; replacing a required document revokes approval.
        if ($user->isProvider()
            && ! $user->providerProfile()->first()?->isVerified()
            && ! $request->is(
                'api/provider/ping',
                'api/provider/verification/documents',
                'api/provider/verification',
                'api/provider/profile',
                'api/provider/skills',
                'api/provider/service-areas',
                'api/provider/work-experiences*',
            )) {
            return ApiResponse::error('Complete government ID and business permit review before using provider features.', [], 403);
        }

        return $next($request);
    }
}
