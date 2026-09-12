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

        if (! $user->status->canSignIn()) {
            return ApiResponse::error('This account has been suspended.', [], 403);
        }

        if (! in_array($user->role->value, $roles, true)) {
            return ApiResponse::error('You are not allowed to perform this action.', [], 403);
        }

        return $next($request);
    }
}
