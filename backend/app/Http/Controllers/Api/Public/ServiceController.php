<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Public search/browse. Only ever returns published, active services from
     * verified providers - an unverified provider's services are invisible
     * to clients no matter what filters are applied.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->published()
            ->active()
            ->whereHas('providerProfile', fn ($q) => $q->verified())
            ->with(['category', 'providerProfile.user', 'providerProfile.baseBarangay']);

        if ($keyword = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($category = $request->string('category')->trim()->toString()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($barangayId = $request->integer('barangay_id')) {
            $query->whereHas(
                'providerProfile',
                fn ($q) => $q->where('base_barangay_id', $barangayId)
                    ->orWhereHas('serviceAreas', fn ($a) => $a->where('barangay_id', $barangayId))
            );
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        $query = match ($request->string('sort')->toString()) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->join('provider_profiles', 'provider_profiles.id', '=', 'services.provider_profile_id')
                ->orderByDesc('provider_profiles.rating_avg')
                ->select('services.*'),
            'most_booked' => $query->withCount('bookings')->orderByDesc('bookings_count'),
            'nearest' => $this->applyNearestSort($query, $request),
            default => $query->orderByDesc('services.created_at'),
        };

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated($query->paginate($perPage), ServiceResource::class);
    }

    public function show(Service $service): JsonResponse
    {
        if (! $service->isPublished() || ! $service->is_active || ! $service->providerProfile->isVerified()) {
            return ApiResponse::error('This service is not available.', [], 404);
        }

        $service->load(['category', 'providerProfile.user', 'providerProfile.baseBarangay', 'providerProfile.skills']);

        return ApiResponse::ok(new ServiceResource($service));
    }

    /**
     * R-17, no external API: bounding-box prefilter then Haversine ordering
     * on the indexed provider_profiles lat/lng columns. Without real client
     * coordinates we never fabricate a distance - fall back to a
     * barangay-match-first ordering, or newest if no barangay was given
     * either.
     */
    private function applyNearestSort($query, Request $request)
    {
        if (! $request->filled('lat') || ! $request->filled('lng')) {
            if ($barangayId = $request->integer('barangay_id')) {
                return $query->join('provider_profiles', 'provider_profiles.id', '=', 'services.provider_profile_id')
                    ->orderByRaw('provider_profiles.base_barangay_id = ? DESC', [$barangayId])
                    ->select('services.*');
            }

            return $query->orderByDesc('services.created_at');
        }

        $lat = $request->float('lat');
        $lng = $request->float('lng');
        $delta = 0.5; // ~55km box, generous for a single municipality

        return $query->join('provider_profiles', 'provider_profiles.id', '=', 'services.provider_profile_id')
            ->whereBetween('provider_profiles.latitude', [$lat - $delta, $lat + $delta])
            ->whereBetween('provider_profiles.longitude', [$lng - $delta, $lng + $delta])
            ->selectRaw('services.*, (6371 * ACOS(
                    COS(RADIANS(?)) * COS(RADIANS(provider_profiles.latitude))
                  * COS(RADIANS(provider_profiles.longitude) - RADIANS(?))
                  + SIN(RADIANS(?)) * SIN(RADIANS(provider_profiles.latitude))
                )) AS distance_km', [$lat, $lng, $lat])
            ->orderBy('distance_km');
    }
}
