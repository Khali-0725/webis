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
            // when the model actually has one. Relative, not url() - the SPA
            // is served from a different origin than this API in production
            // (Vercel -> Render) and reaches /api/* through same-origin
            // rewrite proxies (vercel.json / the Vite dev proxy) specifically
            // so the Sanctum session cookie travels; an absolute APP_URL
            // link here would bypass that proxy and the request would arrive
            // with no cookie at all.
            'qr_image_url' => $this->qr_image_path ? '/api/files/payment-qr/'.$this->id : null,
        ];
    }
}
