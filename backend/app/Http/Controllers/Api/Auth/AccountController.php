<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * The "D" of a user's own account. Soft delete only: bookings, payments,
 * reviews and messages the person was party to remain for the other side
 * and for admin; the row can be restored from Admin > Users > Trash.
 */
class AccountController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if ($user->isAdmin()) {
            throw DomainException::forbidden('An administrator account cannot delete itself. Ask another administrator.');
        }

        if (! Hash::check($validated['password'], $user->password)) {
            throw DomainException::unprocessable('The password you entered is incorrect.');
        }

        $user->delete();

        $this->auth->logout($request);

        return ApiResponse::noContent('Your account has been deleted.');
    }
}
