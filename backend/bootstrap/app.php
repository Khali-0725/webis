<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\Api\ExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Registers POST /api/broadcasting/auth. The `api` prefix is deliberate:
    // the Vercel rewrite proxy only forwards /api/* and /sanctum/*, and the
    // channel-auth request must ride the same first-party Sanctum session as
    // every other API call.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * The SPA authenticates over Sanctum's stateful cookie session.
         *
         * EnsureFrontendRequestsAreStateful must run first: for requests whose
         * Origin/Referer matches config('sanctum.stateful') it injects the
         * cookie-encryption, session and request-forgery middleware, which is
         * what turns an /api/* call into an authenticated, CSRF-protected
         * first-party request.
         *
         * NOTE: request-forgery protection is deliberately NOT excluded for
         * api/*. Excluding it would switch off exactly the protection Sanctum
         * just installed and leave the SPA session open to cross-site request
         * forgery. Third-party clients that cannot hold a session are
         * unaffected - they authenticate with a bearer token, which the
         * middleware skips.
         */
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            HandleCors::class,
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ExceptionRenderer::register($exceptions);
    })->create();
