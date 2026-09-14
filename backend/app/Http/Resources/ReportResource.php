<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The reporter's own view of a report - deliberately narrower than
 * Admin\ReportResource: no handler identity, no internal handling notes.
 *
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => strtolower(class_basename($this->reportable_type)),
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'details' => $this->details,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'handled_at' => $this->handled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
