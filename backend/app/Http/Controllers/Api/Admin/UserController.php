<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', Rule::in(UserRole::values())],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'search' => ['nullable', 'string', 'max:150'],
            'trashed' => ['nullable', Rule::in(['only', 'with'])],
        ]);

        $query = TrashFilter::apply(User::query(), $request);

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

    /**
     * Admin-created accounts skip email verification - the admin vouches for
     * the address - and, like self-registration, a provider gets an empty
     * ProviderProfile so the rest of the system can assume one exists.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = UserRole::from($data['role']);

        $user = DB::transaction(function () use ($data, $role) {
            $user = new User;
            $user->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'barangay_id' => $data['barangay_id'] ?? null,
            ]);
            // Never mass-assigned - see User::$fillable.
            $user->role = $role;
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->save();

            if ($role === UserRole::Provider) {
                $user->providerProfile()->create([]);
            }

            return $user;
        });

        AuditLogger::record($request->user(), 'user.created', $user, ['role' => $role->value], $request);

        return ApiResponse::created(new UserResource($user->fresh()), 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $request) {
            $user->fill(collect($data)->only(['first_name', 'last_name', 'email', 'phone', 'barangay_id'])->all());

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            if (array_key_exists('role', $data)) {
                $role = UserRole::from($data['role']);

                if ($user->id === $request->user()->id && $role !== UserRole::Admin) {
                    throw DomainException::forbidden('You cannot remove your own administrator role.');
                }

                $user->role = $role;

                if ($role === UserRole::Provider && ! $user->providerProfile()->exists()) {
                    $user->providerProfile()->create([]);
                }
            }

            $user->save();
        });

        AuditLogger::record($request->user(), 'user.updated', $user, ['fields' => array_keys($data)], $request);

        return ApiResponse::ok(new UserResource($user->fresh()), 'User updated successfully.');
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

    /**
     * Soft delete: the row (and every booking, payment and message that
     * references it) stays intact, but the account can no longer sign in
     * and disappears from every listing until restored.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            throw DomainException::forbidden('You cannot delete your own account from here.');
        }

        $user->delete();

        AuditLogger::record($request->user(), 'user.deleted', $user, [], $request);

        return ApiResponse::noContent('User moved to trash. It can be restored.');
    }

    public function restore(Request $request, User $user): JsonResponse
    {
        if (! $user->trashed()) {
            throw DomainException::conflict('This user is not deleted.');
        }

        $user->restore();

        AuditLogger::record($request->user(), 'user.restored', $user, [], $request);

        return ApiResponse::ok(new UserResource($user->fresh()), 'User restored.');
    }
}
