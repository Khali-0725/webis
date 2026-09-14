<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            || $user->providerProfile?->id === $booking->provider_profile_id
            || $user->isAdmin();
    }

    /**
     * The exact address/pin (R-26): the client always sees it, the provider
     * only once the booking has moved past Pending, admin always (audited
     * separately by the controller).
     */
    public function viewLocation(User $user, Booking $booking): bool
    {
        if ($user->id === $booking->client_id || $user->isAdmin()) {
            return true;
        }

        if ($user->providerProfile?->id === $booking->provider_profile_id) {
            return $booking->status !== BookingStatus::Pending;
        }

        return false;
    }

    public function accept(User $user, Booking $booking): bool
    {
        return $user->providerProfile?->id === $booking->provider_profile_id;
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $this->accept($user, $booking);
    }

    /**
     * Coarse gate only: provider, the client, or an admin may call this
     * endpoint. Which specific target status each of them may actually set
     * (provider-only for in_progress/completed, client-or-admin for
     * disputed) is enforced by BookingStateMachine::authorizeTransition().
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $this->accept($user, $booking)
            || $user->id === $booking->client_id
            || $user->isAdmin();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id || $user->providerProfile?->id === $booking->provider_profile_id;
    }

    /**
     * Only the client who made the booking pays for it.
     */
    public function submitProof(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id;
    }

    /**
     * Only the client who made the booking rates it (§17: "rating by
     * unrelated users" is one of the explicit "Prevent" rules).
     */
    public function submitReview(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id;
    }

    /**
     * A client may edit the notes/slot of their own request only while it
     * is still Pending - once the provider has accepted, the agreed slot is
     * a commitment on both sides and any change goes through cancel + rebook.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id && $booking->status === BookingStatus::Pending;
    }

    /**
     * Soft delete. Admin always; the client only once the booking no longer
     * occupies the provider's calendar (rejected/cancelled/completed/
     * disputed) - it is then history they may tidy away, not an in-flight
     * job they could vanish from under the provider.
     */
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $booking->client_id
            && ! in_array($booking->status, BookingStatus::slotBlocking(), true);
    }

    public function restore(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }
}
