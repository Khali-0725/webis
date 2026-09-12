<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per §9.3: admins see the flagged message and the violation record only -
 * never the surrounding conversation. This resource is built entirely from
 * `attempted_body`, never from the Conversation/Message models.
 *
 * @mixin \App\Models\ChatViolation
 */
class ChatViolationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ] : null),
            'attempted_body' => $this->attempted_body,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'matched_rule' => $this->matched_rule,
            'severity' => $this->severity->value,
            'action_taken' => $this->action_taken->value,
            'admin_status' => $this->admin_status->value,
            'admin_status_label' => $this->admin_status->label(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
