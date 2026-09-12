<?php

namespace App\Policies;

use App\Models\ProviderAvailabilityException;
use App\Models\User;

class ProviderAvailabilityExceptionPolicy
{
    public function delete(User $user, ProviderAvailabilityException $exception): bool
    {
        return $user->providerProfile?->id === $exception->provider_profile_id;
    }
}
