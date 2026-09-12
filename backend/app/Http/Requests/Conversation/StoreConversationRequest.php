<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_profile_id' => ['required_without:booking_id', 'nullable', 'exists:provider_profiles,id'],
            'booking_id' => ['required_without:provider_profile_id', 'nullable', 'exists:bookings,id'],
        ];
    }
}
