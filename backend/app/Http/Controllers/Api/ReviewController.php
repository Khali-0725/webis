<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyToReviewRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not audience-namespaced: client, provider, and (for listing) admin all
 * touch the same `reviews` resource, scoped by ReviewPolicy/BookingPolicy -
 * same shape as BookingController/PaymentController/ConversationController.
 */
class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    /**
     * GET /bookings/{booking}/review - null if not yet reviewed, so the
     * booking detail page knows whether to show the form or the result.
     */
    public function forBooking(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $review = $booking->review()->with(['client', 'booking.service'])->first();

        return ApiResponse::ok($review ? new ReviewResource($review) : null);
    }

    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('submitReview', $booking);

        $review = $this->reviews->create(
            $booking,
            $request->user(),
            $request->validated('rating'),
            $request->validated('comment'),
        );

        return ApiResponse::created(new ReviewResource($review), 'Review submitted.');
    }

    /**
     * GET /me/reviews - the caller's own written reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->where('client_id', $request->user()->id)
            ->with(['client', 'booking.service'])
            ->orderByDesc('created_at')
            ->paginate(config('webis.pagination.default'));

        return ApiResponse::paginated($reviews, ReviewResource::class);
    }

    /**
     * GET /provider/reviews - the caller's own received reviews, including
     * ones they've hidden (the owner should see what they hid).
     */
    public function providerReviews(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        $reviews = Review::query()
            ->where('provider_profile_id', $profile->id)
            ->with(['client', 'booking.service'])
            ->orderByDesc('created_at')
            ->paginate(config('webis.pagination.default'));

        return ApiResponse::paginated($reviews, ReviewResource::class);
    }

    public function reply(ReplyToReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorize('reply', $review);

        $updated = $this->reviews->reply($review, $request->validated('provider_reply'));

        return ApiResponse::ok(new ReviewResource($updated->load(['client', 'booking.service'])), 'Reply posted.');
    }

    /**
     * PATCH /reviews/{review} - the author edits their rating/comment.
     */
    public function update(StoreReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorize('update', $review);

        $updated = $this->reviews->update($review, $request->validated('rating'), $request->validated('comment'));

        return ApiResponse::ok(new ReviewResource($updated->load(['client', 'booking.service'])), 'Review updated.');
    }

    /**
     * DELETE /reviews/{review} - the author withdraws it (soft delete).
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->reviews->delete($review);

        return ApiResponse::noContent('Review deleted.');
    }

    /**
     * DELETE /reviews/{review}/reply - the provider removes their reply.
     */
    public function removeReply(Request $request, Review $review): JsonResponse
    {
        $this->authorize('reply', $review);

        $updated = $this->reviews->removeReply($review);

        return ApiResponse::ok(new ReviewResource($updated->load(['client', 'booking.service'])), 'Reply removed.');
    }

    public function setVisibility(Request $request, Review $review): JsonResponse
    {
        $this->authorize('setVisibility', $review);

        $validated = $request->validate(['is_visible' => ['required', 'boolean']]);

        $updated = $this->reviews->setVisibility($review, $validated['is_visible']);

        return ApiResponse::ok(new ReviewResource($updated->load(['client', 'booking.service'])), 'Review updated.');
    }
}
