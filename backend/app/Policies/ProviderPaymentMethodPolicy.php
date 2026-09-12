<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Payment;
use App\Models\ProviderPaymentMethod;
use App\Models\User;

class ProviderPaymentMethodPolicy
{
    public function update(User $user, ProviderPaymentMethod $method): bool
    {
        return $user->providerProfile?->id === $method->provider_profile_id;
    }

    public function delete(User $user, ProviderPaymentMethod $method): bool
    {
        return $this->update($user, $method);
    }

    /**
     * The QR image itself is gated separately from the owner-only management
     * endpoints: a client may see it once they actually have an accepted
     * booking paid through this method, not just because they know its id.
     */
    public function viewQr(User $user, ProviderPaymentMethod $method): bool
    {
        if ($this->update($user, $method) || $user->isAdmin()) {
            return true;
        }

        return Payment::query()
            ->where('provider_payment_method_id', $method->id)
            ->where('client_id', $user->id)
            ->whereHas('booking', fn ($q) => $q->where('status', '!=', BookingStatus::Pending))
            ->exists();
    }
}
