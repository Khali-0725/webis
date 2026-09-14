<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->client_id
            || $user->providerProfile?->id === $payment->provider_profile_id
            || $user->isAdmin();
    }

    /**
     * The provider being paid, or admin as a logged override (per the
     * requirements audit's role matrix - not the primary path, but allowed).
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->providerProfile?->id === $payment->provider_profile_id || $user->isAdmin();
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $this->verify($user, $payment);
    }

    /**
     * A financial record is only ever soft-deleted, and only by an admin -
     * neither party to the payment may make it disappear from the other.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
