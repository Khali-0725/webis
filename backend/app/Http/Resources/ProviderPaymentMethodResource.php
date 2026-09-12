<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProviderPaymentMethod
 */
class ProviderPaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'account_name' => $this->account_name,
            'account_ref_masked' => $this->account_ref_masked,
            'instructions' => $this->instructions,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            // Never the raw path - only a served, policy-gated URL, and only
            // when the model actually has one.
            'qr_image_url' => $this->qr_image_path ? url('/api/files/payment-qr/'.$this->id) : null,
        ];
    }
}
