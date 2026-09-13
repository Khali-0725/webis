<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Conversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $viewerIsClient = $viewer && $viewer->id === $this->client_id;

        $other = $viewerIsClient ? $this->whenLoaded('providerUser') : $this->whenLoaded('clientUser');
        $unreadCount = $viewerIsClient ? $this->client_unread_count : $this->provider_unread_count;

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'other_participant' => $other ? [
                'id' => $other->id,
                'full_name' => $other->full_name,
                'initials' => $other->initials,
                // Relative, not url() - see the comment on qr_image_url in
                // ProviderPaymentMethodResource for why.
                'avatar_url' => $other->avatar_path
                    ? '/api/files/avatar/'.$other->id.'?t='.$other->updated_at->timestamp
                    : null,
            ] : null,
            'unread_count' => $unreadCount,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_preview' => $this->whenLoaded(
                'latestMessage',
                fn () => $this->latestMessage?->body,
            ),
        ];
    }
}
