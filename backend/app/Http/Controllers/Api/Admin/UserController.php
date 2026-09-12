<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', Rule::in(UserRole::values())],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $query = User::query();

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $term = '%'.$validated['search'].'%';
            $query->where(fn ($q) => $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated($query->orderByDesc('created_at')->paginate($perPage), UserResource::class);
    }

    public function show(User $user): JsonResponse
    {
        $data = (new UserResource($user))->resolve();

        if ($user->isClient()) {
            $data['booking_count'] = $user->clientBookings()->count();
        } elseif ($user->isProvider()) {
            $profile = $user->providerProfile;
            $data['provider_profile'] = $profile ? [
                'id' => $profile->id,
                'business_name' => $profile->business_name,
                'verification_status' => $profile->verification_status->value,
                'rating_avg' => $profile->rating_avg,
                'rating_count' => $profile->rating_count,
                'completed_bookings_count' => $profile->completed_bookings_count,
            ] : null;
        }

        return ApiResponse::ok($data);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        AuditLogger::record($request->user(), 'user.suspended', $user, [], $request);

        return ApiResponse::ok(null, 'User suspended successfully.');
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $user->forceFill(['status' => UserStatus::Active])->save();

        AuditLogger::record($request->user(), 'user.activated', $user, [], $request);

        return ApiResponse::ok(null, 'User activated successfully.');
    }
}
