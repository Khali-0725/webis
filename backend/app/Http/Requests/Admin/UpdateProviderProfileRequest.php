<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Provider\UpdateProviderProfileRequest as ProviderUpdateProfileRequest;

/**
 * Same field rules as the provider's own profile form - only the gate differs.
 */
class UpdateProviderProfileRequest extends ProviderUpdateProfileRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }
}
