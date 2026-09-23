<?php

namespace App\Policies;

use App\Models\ProviderWorkExperience;
use App\Models\User;

class ProviderWorkExperiencePolicy
{
    public function update(User $user, ProviderWorkExperience $experience): bool
    {
        return $user->providerProfile?->id === $experience->provider_profile_id;
    }

    public function delete(User $user, ProviderWorkExperience $experience): bool
    {
        return $this->update($user, $experience);
    }
}
