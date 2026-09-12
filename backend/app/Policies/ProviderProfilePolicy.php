<?php

namespace App\Policies;

use App\Models\ProviderProfile;
use App\Models\User;

class ProviderProfilePolicy
{
    public function update(User $user, ProviderProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
