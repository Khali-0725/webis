<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request, \App\Services\PasswordService $service): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        try {
            $status = $service->sendResetLink($request->email);
        } catch (Throwable $exception) {
            Log::error('Password reset email delivery failed', ['exception' => $exception]);

            return ApiResponse::error('Reset email is temporarily unavailable. Please try again later.', status: 503);
        }

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            Log::warning('Password reset broker returned an unexpected status', ['status' => $status]);

            return ApiResponse::error('Reset email is temporarily unavailable. Please try again later.', status: 503);
        }

        return ApiResponse::ok(null, 'If an account exists, a reset link has been sent.');
    }

    public function reset(Request $request, \App\Services\PasswordService $service): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed'],
        ]);

        $service->reset($request->only('email', 'password', 'token'));
        
        return ApiResponse::ok(null, 'Password has been reset.');
    }
}
