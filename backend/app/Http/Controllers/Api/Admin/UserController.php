<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\Booking;
use App\Models\ChatViolation;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\ProviderProfile;
use App\Models\ProviderVerificationDocument;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Services\AccountSuspensionService;
use App\Support\AdminRecordFilters;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $request->validate(['confirmation' => ['required', Rule::in(['DELETE'])]]);

        $paths = DB::transaction(function () use ($request, $user): array {
            $user = User::withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $user->trashed()) {
                throw DomainException::conflict('Move this user to Trash before deleting permanently.');
            }
            $profile = $user->providerProfile()->withTrashed()->lockForUpdate()->first();
            $profileId = $profile?->id;
            $id = $user->id;
            $serviceIds = $profile?->services()->withTrashed()->lockForUpdate()->pluck('id')->all() ?? [];
            $documentIds = $profile?->verificationDocuments()->pluck('id')->all() ?? [];
            $bookingIds = DB::table('bookings')->where('client_id', $id)
                ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))
                ->orWhereIn('service_id', $serviceIds)->lockForUpdate()->pluck('id')->all();
            $bookingCodes = DB::table('bookings')->whereIn('id', $bookingIds)->pluck('booking_code');
            $paymentIds = DB::table('payments')->where('client_id', $id)
                ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))
                ->orWhereIn('booking_id', $bookingIds)
                ->orWhereIn('id', DB::table('payment_proofs')->where('uploaded_by', $id)->select('payment_id'))
                ->lockForUpdate()->pluck('id')->all();
            $proofIds = DB::table('payment_proofs')->whereIn('payment_id', $paymentIds)
                ->orWhere('uploaded_by', $id)->lockForUpdate()->pluck('id')->all();
            $conversationIds = DB::table('conversations')->where('client_id', $id)
                ->orWhere('provider_user_id', $id)
                ->orWhereIn('booking_id', $bookingIds)
                ->orWhereIn('id', DB::table('messages')->where('sender_id', $id)->select('conversation_id'))
                ->pluck('id')->all();
            $messageIds = DB::table('messages')->whereIn('conversation_id', $conversationIds)->pluck('id')->all();
            $reviewIds = DB::table('reviews')->where('client_id', $id)
                ->when($profileId, fn ($query) => $query->orWhere('provider_profile_id', $profileId))
                ->orWhereIn('booking_id', $bookingIds)->pluck('id')->all();
            $appealIds = DB::table('suspension_appeals')->where('user_id', $id)->pluck('id')->all();
            $violationIds = DB::table('chat_violations')->where('user_id', $id)
                ->orWhereIn('conversation_id', $conversationIds)
                ->orWhereIn('message_id', $messageIds)->pluck('id')->all();

            $affected = [
                User::class => [$id],
                ProviderProfile::class => $profileId ? [$profileId] : [],
                ProviderVerificationDocument::class => $documentIds,
                Service::class => $serviceIds,
                Booking::class => $bookingIds,
                Payment::class => $paymentIds,
                PaymentProof::class => $proofIds,
                Conversation::class => $conversationIds,
                Review::class => $reviewIds,
                SuspensionAppeal::class => $appealIds,
                ChatViolation::class => $violationIds,
            ];
            $reports = DB::table('reports')->where('reporter_id', $id);
            foreach ($affected as $type => $ids) {
                if ($ids) {
                    $reports->orWhere(fn ($query) => $query->where('reportable_type', $type)->whereIn('reportable_id', $ids));
                }
            }
            $reportIds = $reports->lockForUpdate()->pluck('id')->all();
            $affected[Report::class] = $reportIds;
            $auditLogs = DB::table('audit_logs')->where('actor_id', $id);
            foreach ($affected as $type => $ids) {
                if ($ids) {
                    $auditLogs->orWhere(fn ($query) => $query->where('auditable_type', $type)->whereIn('auditable_id', $ids));
                }
            }
            $auditLogs->delete();

            $paths = array_filter([$user->avatar_path]);
            if ($profile) {
                $paths = array_merge(
                    $paths,
                    $profile->verificationDocuments()->pluck('file_path')->all(),
                    $profile->paymentMethods()->withTrashed()->pluck('qr_image_path')->filter()->all(),
                );
            }
            $paths = array_merge($paths, DB::table('payment_proofs')->whereIn('id', $proofIds)->lockForUpdate()->pluck('file_path')->all());

            DB::table('reports')->whereIn('id', $reportIds)->delete();
            DB::table('chat_violations')->whereIn('id', $violationIds)->delete();
            DB::table('messages')->whereIn('conversation_id', $conversationIds)->delete();
            DB::table('conversations')->whereIn('id', $conversationIds)->delete();
            DB::table('payment_proofs')->whereIn('id', $proofIds)->delete();
            DB::table('payments')->whereIn('id', $paymentIds)->delete();
            DB::table('reviews')->whereIn('id', $reviewIds)->delete();
            DB::table('booking_status_histories')->whereIn('booking_id', $bookingIds)->delete();
            DB::table('bookings')->whereIn('id', $bookingIds)->delete();
            DB::table('suspension_appeals')->where('user_id', $id)->delete();
            if ($bookingCodes->isNotEmpty()) {
                DB::table('notifications')->whereIn('data->event', ['payment.proof_submitted', 'payment.verified', 'payment.rejected'])
                    ->where(function ($query) use ($bookingCodes) {
                        foreach ($bookingCodes as $code) {
                            $query->orWhere('data->message', 'like', '%'.$code.'%');
                        }
                    })->delete();
            }

            $user->tokens()->delete();
            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->forceDelete();
            $paths = array_values(array_unique(array_filter($paths)));
            AuditLogger::record($request->user(), 'user.permanently_deleted', $user, ['file_cleanup_paths' => $paths], $request);

            return $paths;
        });

        if ($paths) {
            try {
                if (! Storage::disk(config('webis.uploads.disk'))->delete($paths)) {
                    throw new \RuntimeException('Storage delete returned false.');
                }
            } catch (\Throwable $exception) {
                Log::error('Permanent user deletion left private files for manual cleanup.', [
                    'user_id' => $user->id,
                    'paths' => $paths,
                    'exception' => $exception,
                ]);
                throw new DomainException('Account deleted, but private file cleanup failed. Check the deletion audit log.', 500);
            }
        }

        return ApiResponse::noContent('User permanently deleted.');
    }
}
