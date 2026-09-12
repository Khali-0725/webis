<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /providers/{providerProfile}/reviews - only ever visible reviews,
     * plus the rating distribution the audit doc and §17 both call for.
     */
    public function index(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        $reviews = Review::query()
            ->where('provider_profile_id', $providerProfile->id)
            ->visible()
            ->with(['client', 'booking.service'])
            ->orderByDesc('created_at')
            ->paginate(config('webis.pagination.default'));

        // array_merge() would renumber these integer keys instead of
        // preserving them - the `+` union operator keeps the real counts
        // and only fills in ratings with zero reviews from the default.
        $actualCounts = Review::query()
            ->where('provider_profile_id', $providerProfile->id)
            ->visible()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        $distribution = $actualCounts + [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        krsort($distribution);

        return ApiResponse::paginated($reviews, ReviewResource::class, ['distribution' => $distribution]);
    }
}
