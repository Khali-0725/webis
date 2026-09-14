<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function update(User $user, Service $service): bool
    {
        return $user->providerProfile?->id === $service->provider_profile_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function restore(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
