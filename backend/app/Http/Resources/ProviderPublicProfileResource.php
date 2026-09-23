<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Safe-for-anyone view of a provider profile: no user email/phone, no exact
 * coordinates - only the base barangay, which is a public discovery signal.
 *
 * `age` is exposed only when the provider opted in via `show_age_publicly`.
 * `birthdate` itself must never appear here, even when age is public.
 */
class ProviderPublicProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name ?: $this->whenLoaded('user', fn () => $this->user->full_name),
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'age' => $this->when($this->show_age_publicly && $this->birthdate, fn () => $this->age()),
            'verification_status' => $this->verification_status->value,
            'is_verified' => $this->isVerified(),
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'completed_bookings_count' => $this->completed_bookings_count,
            'is_accepting_bookings' => $this->is_accepting_bookings,
            'base_barangay' => new BarangayResource($this->whenLoaded('baseBarangay')),
            'skills' => $this->whenLoaded('skills', fn () => $this->skills->pluck('skill')),
            'service_areas' => $this->whenLoaded('serviceAreas', fn () => $this->serviceAreas->pluck('barangay.name')),
            'work_experiences' => $this->whenLoaded('workExperiences', fn () => ProviderWorkExperienceResource::collection($this->workExperiences)),
        ];
    }
}
