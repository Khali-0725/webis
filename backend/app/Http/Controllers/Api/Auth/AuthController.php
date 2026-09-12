<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register($request->validated());

        // Sign the new account in immediately so the SPA can go straight to
        // the role dashboard instead of bouncing through the login form.
        $this->auth->login($request, $user->email, $request->validated('password'));

        return ApiResponse::created(
            new UserResource($user->fresh()),
            'Welcome to WEBIS, '.$user->first_name.'.'
        );
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'login:'.$request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Too many sign-in attempts. Try again in {$seconds} seconds."],
            ])->status(429);
        }

        try {
            $user = $this->auth->login(
                $request,
                $request->validated('email'),
                $request->validated('password'),
                (bool) $request->boolean('remember'),
            );
        } catch (\Throwable $e) {
            RateLimiter::hit($key, decaySeconds: 60);

            throw $e;
        }

        RateLimiter::clear($key);

        return ApiResponse::ok(
            new UserResource($user),
            'Signed in successfully.'
        );
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return ApiResponse::noContent('Signed out.');
    }

    /**
     * GET /api/auth/me
     *
     * Called once on SPA boot to rehydrate the session.
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::ok(new UserResource($request->user()));
    }
}
