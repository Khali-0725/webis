<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(int $id, string $hash, \App\Services\VerificationService $service): JsonResponse
    {
        $service->verify($id, $hash);
        return ApiResponse::ok(null, 'Email verified successfully.');
    }

    public function resend(Request $request, \App\Services\VerificationService $service): JsonResponse
    {
        $service->resend($request->user());
        return ApiResponse::ok(null, 'Verification link resent.');
    }
}
