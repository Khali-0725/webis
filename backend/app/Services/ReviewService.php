<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rating + review (§17 / R-34..R-36). `provider_profiles.rating_avg`/
 * `rating_count` are cached aggregates - recomputed here on every write,
 * never read live at search time (see ProviderProfile's own docblock).
 * `DemoDataSeeder` backfills these with the exact same formula.
 */
class ReviewService
{
    public function create(Booking $booking, User $client, int $rating, ?string $comment): Review
    {
        return DB::transaction(function () use ($booking, $client, $rating, $comment) {
            if ($booking->status !== BookingStatus::Completed) {
                throw DomainException::forbidden('You can only review a booking after it has been completed.');
            }

            if ($booking->review()->exists()) {
                throw DomainException::conflict('This booking has already been reviewed.');
            }

            $review = Review::create([
                'booking_id' => $booking->id,
                'client_id' => $client->id,
                'provider_profile_id' => $booking->provider_profile_id,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            $this->recomputeAggregates($booking->provider_profile_id);

            return $review->fresh(['client', 'booking.service']);
        });
    }

    public function reply(Review $review, string $replyText): Review
    {
        $review->forceFill([
            'provider_reply' => $replyText,
            'replied_at' => now(),
        ])->save();

        return $review->fresh();
    }

    public function setVisibility(Review $review, bool $visible): Review
    {
        return DB::transaction(function () use ($review, $visible) {
            $review->forceFill(['is_visible' => $visible])->save();

            $this->recomputeAggregates($review->provider_profile_id);

            return $review->fresh();
        });
    }

    private function recomputeAggregates(int $providerProfileId): void
    {
        $visible = Review::where('provider_profile_id', $providerProfileId)->where('is_visible', true);

        $count = $visible->count();
        $avg = $count > 0 ? $visible->avg('rating') : 0;

        ProviderProfile::whereKey($providerProfileId)->update([
            'rating_avg' => round($avg, 2),
            'rating_count' => $count,
        ]);
    }
}
