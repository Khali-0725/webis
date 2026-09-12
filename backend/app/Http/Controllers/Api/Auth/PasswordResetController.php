<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request, \App\Services\PasswordService $service): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $service->sendResetLink($request->email);
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
