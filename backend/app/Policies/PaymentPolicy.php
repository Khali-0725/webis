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
}
