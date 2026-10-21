<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderPublicProfileResource;
use App\Http\Resources\ServiceResource;
use App\Models\ProviderProfile;
use App\Enums\UserStatus;
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
            'municipality' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
        ]);

        $query = ProviderProfile::verified()
            ->acceptingBookings()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active->value)->whereNull('terminated_at')->whereNull('deleted_at'))
            ->with(['user', 'baseBarangay']);

        if (! empty($validated['search'])) {
            $term = '%'.$validated['search'].'%';
            $query->where(fn ($q) => $q->where('business_name', 'like', $term)
                ->orWhereHas('skills', fn ($skill) => $skill->where('skill', 'like', $term)));
        }

        if (! empty($validated['barangay_id'])) {
            $query->where(fn ($q) => $q->where('base_barangay_id', $validated['barangay_id'])
                ->orWhereHas('serviceAreas', fn ($area) => $area->where('barangay_id', $validated['barangay_id'])));
        }
        if (! empty($validated['municipality'])) {
            $query->where(fn ($q) => $q->whereHas('baseBarangay', fn ($area) => $area->where('municipality', $validated['municipality']))
                ->orWhereHas('serviceAreas.barangay', fn ($area) => $area->where('municipality', $validated['municipality'])));
        }
        if (! empty($validated['category_id'])) {
            $query->whereHas('services', fn ($service) => $service->published()->active()
                ->where('service_category_id', $validated['category_id']));
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        $providers = $query->orderByDesc('rating_avg')->orderByDesc('rating_count')
            ->paginate($perPage);

        return ApiResponse::paginated($providers, ProviderPublicProfileResource::class);
    }

    public function map(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'municipality' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
        ]);
        $providers = ProviderProfile::verified()->acceptingBookings()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active->value)->whereNull('terminated_at')->whereNull('deleted_at'))
            ->whereHas('services', fn ($q) => $q->published()->active()
                ->when($validated['category_id'] ?? null, fn ($s, $id) => $s->where('service_category_id', $id)))
            ->when($validated['municipality'] ?? null, fn ($q, $municipality) => $q->where(function ($eligible) use ($municipality) {
                $eligible->whereHas('serviceAreas.barangay', fn ($area) => $area->where('municipality', $municipality)
                    ->where('is_active', true)->whereNotNull('latitude')->whereNotNull('longitude'))
                    ->orWhere(fn ($fallback) => $fallback->whereDoesntHave('serviceAreas')
                        ->whereHas('baseBarangay', fn ($area) => $area->where('municipality', $municipality)
                            ->where('is_active', true)->whereNotNull('latitude')->whereNotNull('longitude')));
            }))
            ->with(['user', 'baseBarangay', 'serviceAreas.barangay'])
            ->limit(300)->get();

        $pins = $providers->flatMap(function (ProviderProfile $profile) use ($validated) {
            $areas = $profile->serviceAreas->pluck('barangay')->filter();
            if ($areas->isEmpty() && $profile->baseBarangay) $areas = collect([$profile->baseBarangay]);
            return $areas->filter(fn ($area) => $area->is_active
                    && $area->latitude !== null && $area->longitude !== null
                    && (empty($validated['municipality']) || $area->municipality === $validated['municipality']))
                ->map(fn ($area) => [
                    'provider_id' => $profile->id,
                    'business_name' => $profile->business_name ?: $profile->user?->full_name,
                    'barangay' => $area->name,
                    'municipality' => $area->municipality,
                    'latitude' => $area->latitude,
                    'longitude' => $area->longitude,
                ]);
        })->take(600)->values();

        return ApiResponse::ok($pins);
    }

    public function show(ProviderProfile $providerProfile): JsonResponse
    {
        if (! $providerProfile->isVerified() || ! $providerProfile->is_accepting_bookings
            || ! $providerProfile->user?->canAccessAccount()) {
            return ApiResponse::error('This provider is not available.', [], 404);
        }

        $providerProfile->load(['user', 'baseBarangay', 'skills', 'serviceAreas.barangay', 'workExperiences']);

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
