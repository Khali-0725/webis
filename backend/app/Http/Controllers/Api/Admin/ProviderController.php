<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\VerificationStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProviderProfileRequest;
use App\Http\Resources\Admin\ProviderProfileResource;
use App\Http\Resources\ServiceResource;
use App\Models\ProviderProfile;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Oversight + moderation of provider profiles. Verification approve/reject
 * still lives in VerificationController - this is the profile record
 * itself (drill-down, content edits, and soft delete/restore).
 */
class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['nullable', Rule::in(VerificationStatus::values())],
            'search' => ['nullable', 'string', 'max:150'],
            'trashed' => ['nullable', Rule::in(['only', 'with'])],
        ]);

        $query = TrashFilter::apply(ProviderProfile::query(), $request)->with(['user', 'baseBarangay']);

        if (! empty($validated['verification_status'])) {
            $query->where('verification_status', $validated['verification_status']);
        }

        if (! empty($validated['search'])) {
            $query->where('business_name', 'like', '%'.$validated['search'].'%');
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated($query->orderByDesc('created_at')->paginate($perPage), ProviderProfileResource::class);
    }

    public function show(ProviderProfile $providerProfile): JsonResponse
    {
        $providerProfile->load(['user', 'baseBarangay', 'skills', 'serviceAreas', 'verificationDocuments']);

        $data = (new ProviderProfileResource($providerProfile))->resolve();
        $data['bio'] = $providerProfile->bio;
        $data['experience_years'] = $providerProfile->experience_years;
        $data['base_barangay_id'] = $providerProfile->base_barangay_id;
        $data['services'] = ServiceResource::collection($providerProfile->services()->with('category')->get())->resolve();
        $data['recent_bookings_count'] = $providerProfile->bookings()->count();
        $data['verification_documents'] = $providerProfile->verificationDocuments->map(fn ($doc) => [
            'id' => $doc->id,
            'document_type' => $doc->document_type->value,
            'status' => $doc->status->value,
            'created_at' => $doc->created_at?->toIso8601String(),
        ]);

        return ApiResponse::ok($data);
    }

    public function update(UpdateProviderProfileRequest $request, ProviderProfile $providerProfile): JsonResponse
    {
        $providerProfile->update($request->validated());

        AuditLogger::record($request->user(), 'provider.updated', $providerProfile, ['fields' => array_keys($request->validated())], $request);

        return ApiResponse::ok(new ProviderProfileResource($providerProfile->fresh(['user', 'baseBarangay'])), 'Provider updated.');
    }

    /**
     * Soft delete: the profile leaves search, the directory and every admin
     * list, but bookings/payments/reviews that reference it keep resolving
     * (those relations use withTrashed()). The owning user account is left
     * alone - suspend or delete it separately from Users.
     */
    public function destroy(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        $providerProfile->delete();

        AuditLogger::record($request->user(), 'provider.deleted', $providerProfile, [], $request);

        return ApiResponse::noContent('Provider profile moved to trash.');
    }

    public function restore(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        if (! $providerProfile->trashed()) {
            throw DomainException::conflict('This provider is not deleted.');
        }

        $providerProfile->restore();

        AuditLogger::record($request->user(), 'provider.restored', $providerProfile, [], $request);

        return ApiResponse::ok(new ProviderProfileResource($providerProfile->fresh(['user', 'baseBarangay'])), 'Provider restored.');
    }
}
