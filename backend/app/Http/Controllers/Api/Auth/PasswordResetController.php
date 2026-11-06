<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request, PasswordResetOtpService $service): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        try {
            $service->request($data['email']);
        } catch (Throwable $exception) {
            Log::error('Password reset email delivery failed', ['exception' => $exception]);

            return ApiResponse::error('Reset email is temporarily unavailable. Please try again later.', status: 503);
        }

        return ApiResponse::ok(null, 'If an account exists, a reset code has been sent.');
    }

    public function verifyCode(Request $request, PasswordResetOtpService $service): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        return ApiResponse::ok(['grant' => $service->verify($data['email'], $data['code'])]);
    }

    public function reset(Request $request, PasswordResetOtpService $service): JsonResponse
    {
        $data = $request->validate([
            'grant' => ['required', 'string', 'size:64'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ]);

        $service->reset($data['email'], $data['grant'], $data['password']);

        return ApiResponse::ok(null, 'Password has been reset.');
    }
}
