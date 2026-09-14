<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'is_mine' => $request->user()?->id === $this->sender_id,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'full_name' => $this->sender->full_name,
                'initials' => $this->sender->initials,
                // Relative, not url() - see the comment on qr_image_url in
                // ProviderPaymentMethodResource for why.
                'avatar_url' => $this->sender->avatar_path
                    ? '/api/files/avatar/'.$this->sender->id.'?t='.$this->sender->updated_at->timestamp
                    : null,
            ]),
            'body' => $this->body,
            'is_flagged' => $this->moderation_status->value === 'flagged',
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
        ];
    }
}
