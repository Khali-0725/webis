<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Provider\UpdateServiceRequest as ProviderUpdateServiceRequest;

/**
 * Same field rules as the provider's own edit form - only the gate differs.
 */
class UpdateServiceRequest extends ProviderUpdateServiceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }
}
