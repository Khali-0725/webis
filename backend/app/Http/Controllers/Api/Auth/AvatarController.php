<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class AvatarController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', config('webis.uploads.disk'));

        if ($user->avatar_path) {
            Storage::disk(config('webis.uploads.disk'))->delete($user->avatar_path);
        }

        $user->avatar_path = $path;
        $user->save();

        return ApiResponse::ok(null, 'Avatar updated successfully.');
    }
}
