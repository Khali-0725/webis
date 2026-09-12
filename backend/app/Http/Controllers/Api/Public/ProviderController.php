<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderPublicProfileResource;
use App\Http\Resources\ServiceResource;
use App\Models\ProviderProfile;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * Public directory of verified providers - a browse/discovery view,
     * distinct from `Public\ServiceController::index()`'s per-service search.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'barangay_id' => ['nullable', 'integer'],
        ]);

        $query = ProviderProfile::verified()
            ->with(['user', 'baseBarangay']);

        if (! empty($validated['search'])) {
            $term = '%'.$validated['search'].'%';
            $query->where(fn ($q) => $q->where('business_name', 'like', $term)
                ->orWhereHas('skills', fn ($skill) => $skill->where('skill', 'like', $term)));
        }

        if (! empty($validated['barangay_id'])) {
            $query->where('base_barangay_id', $validated['barangay_id']);
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        $providers = $query->orderByDesc('rating_avg')->orderByDesc('rating_count')
            ->paginate($perPage);

        return ApiResponse::paginated($providers, ProviderPublicProfileResource::class);
    }

    public function show(ProviderProfile $providerProfile): JsonResponse
    {
        if (! $providerProfile->isVerified()) {
            return ApiResponse::error('This provider is not available.', [], 404);
        }

        $providerProfile->load(['user', 'baseBarangay', 'skills', 'serviceAreas.barangay']);

        $services = $providerProfile->services()
            ->published()
            ->active()
            ->with('category')
            ->latest('published_at')
            ->get();

        $data = (new ProviderPublicProfileResource($providerProfile))->resolve();
        $data['services'] = ServiceResource::collection($services)->resolve();

        return ApiResponse::ok($data);
    }
}
