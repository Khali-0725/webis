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

    /**
     * The review's author may edit or withdraw it. Hiding stays the
     * provider's tool; editing/deleting is the client's.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->client_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $this->update($user, $review);
    }
}
