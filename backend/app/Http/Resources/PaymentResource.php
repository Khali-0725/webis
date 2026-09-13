<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = $this->whenLoaded('booking');
        // The QR/instructions only make sense once the provider has actually
        // accepted the job - mirrors the location-privacy gate from Phase 5,
        // living here in the Resource rather than hidden in the query.
        $showPaymentMethod = $booking && $booking->status !== BookingStatus::Pending;

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference_number' => $this->reference_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'settlement_method' => $this->settlement_method->value,
            'settlement_method_label' => $this->settlement_method->label(),
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'payment_method' => $showPaymentMethod
                ? new ProviderPaymentMethodResource($this->whenLoaded('paymentMethod'))
                : null,
            'current_proof' => $this->whenLoaded('proofs', function () {
                $current = $this->proofs->firstWhere('superseded_at', null);

                return $current ? [
                    'id' => $current->id,
                    'original_name' => $current->original_name,
                    'reference_number' => $current->reference_number,
                    'uploaded_at' => $current->created_at?->toIso8601String(),
                    // Relative, not url() - see the comment on qr_image_url
                    // in ProviderPaymentMethodResource for why.
                    'url' => '/api/files/payment-proof/'.$current->id,
                ] : null;
            }),
        ];
    }
}
