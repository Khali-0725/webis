<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ProviderProfileResource;
use App\Http\Resources\ServiceResource;
use App\Models\ProviderProfile;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Read-oriented oversight - the one real admin action on a provider
 * (verification approve/reject) already lives in VerificationController.
 * This is drill-down, not a second place to mutate the same fields.
 */
class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['nullable', Rule::in(VerificationStatus::values())],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $query = ProviderProfile::query()->with(['user', 'baseBarangay']);

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
}
