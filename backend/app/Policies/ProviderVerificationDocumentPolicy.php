<?php

namespace App\Policies;

use App\Models\ProviderVerificationDocument;
use App\Models\User;

class ProviderVerificationDocumentPolicy
{
    public function view(User $user, ProviderVerificationDocument $document): bool
    {
        return $user->isAdmin() || $user->providerProfile?->id === $document->provider_profile_id;
    }
}
