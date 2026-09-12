<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests whose Origin or Referer matches one of these hosts receive
    | cookie-based session authentication with request-forgery protection,
    | instead of bearer-token authentication.
    |
    | The Vite dev server (:5173) and the production SPA origin belong here.
    | Set SANCTUM_STATEFUL_DOMAINS in .env for anything beyond the defaults.
    |
    */

    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:5173,localhost:3000,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | null = personal access tokens do not expire on their own. First-party SPA
    | sessions are governed by SESSION_LIFETIME in config/session.php instead.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | ValidateCsrfToken is Laravel 13's deprecated alias for
    | Illuminate\Foundation\Http\Middleware\PreventRequestForgery. Sanctum 4.x
    | still references the alias, so it is kept here to match the package.
    |
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
