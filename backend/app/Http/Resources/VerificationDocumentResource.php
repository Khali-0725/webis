<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A provider verification document, as reviewed by an admin.
 *
 * Includes the submitting provider's name/email/phone since the admin needs
 * them to confirm the document matches the account - this is an admin-only
 * endpoint, unlike the public provider resources which withhold contact info.
 *
 * @mixin \App\Models\ProviderVerificationDocument
 */
class VerificationDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->providerProfile->user;

        return [
            'id' => $this->id,
            'document_type' => $this->document_type->value,
            'original_name' => $this->original_name,
            'status' => $this->status->value,
            'review_notes' => $this->review_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'provider' => [
                'id' => $this->providerProfile->id,
                'business_name' => $this->providerProfile->business_name,
                'user_full_name' => $user->full_name,
                'user_email' => $user->email,
                'user_phone' => $user->phone,
            ],
        ];
    }
}
