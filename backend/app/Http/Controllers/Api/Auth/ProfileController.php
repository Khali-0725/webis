<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $values = $request->validated();
        $emailChanged = isset($values['email']) && $values['email'] !== $user->email;
        if ($emailChanged) {
            $values['email_verified_at'] = null;
        }
        $user->forceFill($values)->save();
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return ApiResponse::ok(
            new UserResource($user->fresh()),
            $emailChanged ? 'Profile updated. Verify your new email address.' : 'Profile updated successfully.'
        );
    }
}
