<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Checks account state and browser inactivity independently of API polling. */
class EnforceAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/auth/login', 'api/auth/google', 'api/auth/register', 'api/auth/logout', 'api/appeals', 'api/appeals/status')) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->canAccessAccount()) {
            return ApiResponse::error(
                $user->getRawOriginal('terminated_at') !== null
                    ? 'This account has been terminated.'
                    : 'This account has been suspended.',
                [],
                403,
            );
        }

        if ($request->hasSession() && Auth::guard('web')->check()) {
            $session = $request->session();
            $activeSessionId = $user->getRawOriginal('active_session_id');
            if ($activeSessionId !== null && $activeSessionId !== $session->getId()) {
                $session->invalidate();

                return ApiResponse::error('This account was signed in on another device. Please sign in again.', ['session' => ['replaced']], 401);
            }

            $lastActivity = $session->get('webis.last_user_activity');
            if ($lastActivity !== null && now()->timestamp - (int) $lastActivity >= 1800) {
                $session->invalidate();

                return ApiResponse::error('Your session expired after 30 minutes of inactivity. Please sign in again.', ['session' => ['expired']], 401);
            }

            if ($lastActivity === null) {
                // A session established before this migration gets one bounded window.
                $session->put('webis.last_user_activity', now()->timestamp);
            }

            if ($request->is('api/auth/activity') && $request->isMethod('post')) {
                $session->put('webis.last_user_activity', now()->timestamp);
            }
        }

        return $next($request);
    }
}
