<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full self-view of a provider's own profile - includes fields (lat/lng,
 * rejection reason) that must never appear in the public resource.
 */
class ProviderProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'verification_status' => $this->verification_status->value,
            'verification_status_label' => $this->verification_status->label(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'completed_bookings_count' => $this->completed_bookings_count,
            'is_accepting_bookings' => $this->is_accepting_bookings,
            'base_barangay' => new BarangayResource($this->whenLoaded('baseBarangay')),
            'skills' => $this->whenLoaded('skills', fn () => $this->skills->pluck('skill')),
            'service_areas' => $this->whenLoaded('serviceAreas', fn () => $this->serviceAreas->pluck('barangay_id')),
        ];
    }
}
