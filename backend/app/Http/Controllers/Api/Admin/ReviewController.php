<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Support\AdminRecordFilters;
use App\Services\ReviewService;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trashed' => ['nullable', 'in:only,with'],
            'sort' => ['nullable', 'in:newest,oldest'],
        ]);
        $query = TrashFilter::apply(Review::query(), $request)
            ->with(['client', 'booking.service', 'providerProfile']);
        return ApiResponse::paginated(AdminRecordFilters::apply($query, $request)
            ->paginate(min($request->integer('per_page', 20), 50)), ReviewResource::class);
    }

    public function visibility(Request $request, Review $review, ReviewService $reviews): JsonResponse
    {
        $data = $request->validate([
            'is_visible' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $reviews->setVisibility($review, $data['is_visible']);
        AuditLogger::record($request->user(), 'review.visibility_changed', $review, $data, $request);
        return ApiResponse::ok(new ReviewResource($review->fresh(['client', 'booking.service'])), 'Review visibility updated.');
    }

    public function archive(Request $request, Review $review, ReviewService $reviews): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $reviews->setVisibility($review, false);
        $review->delete();
        AuditLogger::record($request->user(), 'review.archived', $review, $data, $request);
        return ApiResponse::noContent('Review archived.');
    }

    public function restore(Request $request, Review $review): JsonResponse
    {
        if (! $review->trashed()) throw DomainException::conflict('This review is not archived.');
        $review->restore();
        AuditLogger::record($request->user(), 'review.restored', $review, [], $request);
        return ApiResponse::ok(new ReviewResource($review->fresh(['client', 'booking.service'])), 'Review restored as hidden.');
    }
}
