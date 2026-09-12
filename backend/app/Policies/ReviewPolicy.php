<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function reply(User $user, Review $review): bool
    {
        return $user->providerProfile?->id === $review->provider_profile_id;
    }

    public function setVisibility(User $user, Review $review): bool
    {
        return $this->reply($user, $review);
    }
}
