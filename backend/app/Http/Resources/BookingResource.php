<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking's non-sensitive fields. The exact address/pin is never included
 * here - see BookingLocationResource and BookingController::show(), which
 * attaches it only after a `viewLocation` policy check.
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'quoted_price' => $this->quoted_price,
            'final_price' => $this->final_price,
            'client_notes' => $this->client_notes,
            'provider_notes' => $this->provider_notes,
            'cancellation_reason' => $this->cancellation_reason,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'provider' => new ProviderPublicProfileResource($this->whenLoaded('providerProfile')),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'full_name' => $this->client->full_name,
            ]),
        ];
    }
}
