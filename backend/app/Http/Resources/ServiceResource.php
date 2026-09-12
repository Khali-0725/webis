<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'pricing_type' => $this->pricing_type->value,
            'pricing_type_label' => $this->pricing_type->label(),
            'price' => $this->price,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => $this->is_active,
            'is_published' => $this->isPublished(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'category' => new ServiceCategoryResource($this->whenLoaded('category')),
            'provider' => new ProviderPublicProfileResource($this->whenLoaded('providerProfile')),
        ];
    }
}
