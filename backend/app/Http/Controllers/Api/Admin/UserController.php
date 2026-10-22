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
use App\Services\AccountSuspensionService;
use App\Support\AdminRecordFilters;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', Rule::in(UserRole::values())],
            'status' => ['nullable', Rule::in([...UserStatus::values(), 'terminated'])],
            'search' => ['nullable', 'string', 'max:150'],
            'trashed' => ['nullable', Rule::in(['only', 'with'])],
        ]);

        $query = TrashFilter::apply(User::query(), $request);

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (($validated['status'] ?? null) === 'terminated') {
            $query->whereNotNull('terminated_at');
        } elseif (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
            $query->whereNull('terminated_at');
        }

        if (! empty($validated['search'])) {
            $term = '%'.$validated['search'].'%';
            $query->where(fn ($q) => $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated(AdminRecordFilters::apply($query, $request)->paginate($perPage), UserResource::class);
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

    public function suspend(Request $request, User $user, AccountSuspensionService $suspensions): JsonResponse
    {
        if ($user->id === $request->user()->id || $user->terminated_at !== null) {
            throw DomainException::forbidden('This account cannot be suspended.');
        }

        $maximumDays = AccountSuspensionService::maximumDays();
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'basis' => ['required', 'string', 'max:120'],
            'days' => ['required', 'integer', 'between:1,'.$maximumDays],
        ]);

        $suspensions->suspend($user, $request->user(), $data, $request, 'admin_user_management');

        return ApiResponse::ok(null, 'User suspended successfully.');
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        if ($user->terminated_at !== null) {
            throw DomainException::conflict('A terminated account requires a separate restoration decision.');
        }
        $user->forceFill(['status' => UserStatus::Active, 'suspended_until' => null])->save();

        AuditLogger::record($request->user(), 'user.activated', $user, [], $request);

        return ApiResponse::ok(null, 'User activated successfully.');
    }

    public function terminate(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id || $user->terminated_at !== null) {
            throw DomainException::forbidden('This account cannot be terminated.');
        }
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        $user->forceFill([
            'terminated_at' => now(),
            'termination_reason' => $data['reason'],
            'terminated_by' => $request->user()->id,
            'active_session_id' => null,
            'remember_token' => null,
        ])->save();

        AuditLogger::record($request->user(), 'user.terminated', $user, $data, $request);

        return ApiResponse::noContent('Account terminated.');
    }

    public function restoreTerminated(Request $request, User $user): JsonResponse
    {
        if ($user->terminated_at === null) {
            throw DomainException::conflict('This account is not terminated.');
        }
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $user->forceFill([
            'terminated_at' => null,
            'termination_reason' => null,
            'terminated_by' => null,
            'status' => UserStatus::Active,
            'suspended_until' => null,
            'active_session_id' => null,
        ])->save();
        AuditLogger::record($request->user(), 'user.termination_restored', $user, $data, $request);

        return ApiResponse::ok(new UserResource($user->fresh()), 'Account restored.');
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

    public function destroyPermanently(Request $request, User $user): JsonResponse
    {
        if (! $user->trashed()) {
            throw DomainException::conflict('Move this user to Trash before deleting permanently.');
        }

        $paths = DB::transaction(function () use ($request, $user): array {
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $profile = $user->providerProfile()->withTrashed()->first();
            $profileId = $profile?->id;
            $id = $user->id;

            $hasHistory = DB::table('bookings')->where('client_id', $id)
                ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))->exists()
                || DB::table('payments')->where('client_id', $id)
                    ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))->exists()
                || DB::table('conversations')->where('client_id', $id)->orWhere('provider_user_id', $id)->exists()
                || DB::table('messages')->where('sender_id', $id)->exists()
                || DB::table('reviews')->where('client_id', $id)
                    ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))->exists()
                || DB::table('payment_proofs')->where('uploaded_by', $id)->exists()
                || DB::table('suspension_appeals')->where('user_id', $id)->exists();

            if ($hasHistory) {
                throw DomainException::conflict('This account has transaction or conversation history and cannot be permanently deleted.');
            }

            $paths = array_filter([$user->avatar_path]);
            if ($profile) {
                $paths = array_merge(
                    $paths,
                    $profile->verificationDocuments()->pluck('file_path')->all(),
                    $profile->paymentMethods()->withTrashed()->pluck('qr_image_path')->filter()->all(),
                );
            }

            $user->tokens()->delete();
            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            AuditLogger::record($request->user(), 'user.permanently_deleted', $user, [], $request);
            $user->forceDelete();

            return array_values($paths);
        });

        if ($paths) {
            Storage::disk(config('webis.uploads.disk'))->delete($paths);
        }

        return ApiResponse::noContent('User permanently deleted.');
    }
}
