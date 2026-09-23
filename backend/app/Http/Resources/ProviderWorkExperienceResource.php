<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProviderWorkExperience
 */
class ProviderWorkExperienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_title' => $this->role_title,
            'employer_name' => $this->employer_name,
            'description' => $this->description,
            'started_on' => $this->started_on?->toDateString(),
            'ended_on' => $this->ended_on?->toDateString(),
            'is_current' => $this->isCurrent(),
        ];
    }
}
