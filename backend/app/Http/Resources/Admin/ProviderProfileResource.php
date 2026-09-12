<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin oversight view - adds the owning account's name/email on top of the
 * fields the provider's own self-view resource already exposes, since an
 * admin (unlike a public visitor) needs to know who the account belongs to.
 *
 * @mixin \App\Models\ProviderProfile
 */
class ProviderProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'owner' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ]),
            'verification_status' => $this->verification_status->value,
            'verification_status_label' => $this->verification_status->label(),
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'completed_bookings_count' => $this->completed_bookings_count,
            'is_accepting_bookings' => $this->is_accepting_bookings,
            'base_barangay' => $this->whenLoaded('baseBarangay', fn () => $this->baseBarangay?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
