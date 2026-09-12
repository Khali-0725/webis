<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user's own record.
 *
 * Never used for *other* users - a public profile has its own resource so
 * email, phone and status can never leak through a relation load.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? url('/api/files/avatar/'.$this->id).'?t='.$this->updated_at->timestamp : null,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'status' => $this->status->value,
            'email_verified' => $this->email_verified_at !== null,
            'home_path' => $this->role->homePath(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
