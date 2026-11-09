<?php

namespace App\Http\Resources\Admin;

use App\Models\Report;
use App\Models\ProviderProfile;
use App\Services\ProviderReportThreshold;
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
            'reportable_type' => $this->reportable_type === ProviderProfile::class
                ? 'Provider' : class_basename($this->reportable_type),
            'reportable_id' => $this->reportable_id,
            'suspension_review' => $this->target_user_id
                ? app(ProviderReportThreshold::class)->forTargetUserId($this->target_user_id, $request)
                : null,
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'details' => $this->details,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'handled_by' => $this->whenLoaded('handledByUser', fn () => $this->handledByUser?->full_name),
            'handled_at' => $this->handled_at?->toIso8601String(),
            'handling_notes' => $this->handling_notes,
            'provider_violation' => $this->whenLoaded('providerViolation', fn () => $this->providerViolation ? [
                'id' => $this->providerViolation->id,
                'penalty_points' => $this->providerViolation->penalty_points,
                'reason' => $this->providerViolation->reason,
                'issued_at' => $this->providerViolation->created_at?->toIso8601String(),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
