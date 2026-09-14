<?php

namespace App\Http\Resources\Admin;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'full_name' => $this->reporter->full_name,
                'email' => $this->reporter->email,
            ] : null),
            'reportable_type' => class_basename($this->reportable_type),
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'details' => $this->details,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'handled_by' => $this->whenLoaded('handledByUser', fn () => $this->handledByUser?->full_name),
            'handled_at' => $this->handled_at?->toIso8601String(),
            'handling_notes' => $this->handling_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
