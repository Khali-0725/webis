<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'address_line' => $this->address_line,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'landmark_notes' => $this->landmark_notes,
            'barangay' => new BarangayResource($this->whenLoaded('barangay')),
        ];
    }
}
