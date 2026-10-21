<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = $this->whenLoaded('booking');
        // The QR/instructions only make sense once the provider has actually
        // accepted the job - mirrors the location-privacy gate from Phase 5,
        // living here in the Resource rather than hidden in the query.
        $showPaymentMethod = $booking && in_array($booking->status, [BookingStatus::Accepted, BookingStatus::InProgress, BookingStatus::Completed], true);

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
            'created_at' => $this->created_at?->toIso8601String(),
            'receipt_id' => 'WBR-'.str_pad((string) $this->id, 8, '0', STR_PAD_LEFT),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'payment_method' => $showPaymentMethod
                ? new ProviderPaymentMethodResource($this->whenLoaded('paymentMethod'))
                : null,
            'current_proof' => $this->whenLoaded('proofs', function () {
                $current = $this->proofs->firstWhere('superseded_at', null);

                return $current ? [
                    'id' => $current->id,
                    'original_name' => $current->original_name,
                    'reference_number' => $current->reference_number,
                    'declared_amount' => $current->declared_amount,
                    'paid_at' => $current->paid_at?->toIso8601String(),
                    'uploaded_at' => $current->created_at?->toIso8601String(),
                    // Relative, not url() - see the comment on qr_image_url
                    // in ProviderPaymentMethodResource for why.
                    'url' => '/api/files/payment-proof/'.$current->id,
                ] : null;
            }),
            'proof_history' => $this->whenLoaded('proofs', fn () => $this->proofs->map(fn ($proof) => [
                'id' => $proof->id,
                'original_name' => $proof->original_name,
                'reference_number' => $proof->reference_number,
                'declared_amount' => $proof->declared_amount,
                'paid_at' => $proof->paid_at?->toIso8601String(),
                'uploaded_at' => $proof->created_at?->toIso8601String(),
                'superseded_at' => $proof->superseded_at?->toIso8601String(),
                'url' => '/api/files/payment-proof/'.$proof->id,
            ])->values()),
        ];
    }
}
