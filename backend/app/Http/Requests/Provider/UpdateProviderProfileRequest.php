<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:150'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'base_barangay_id' => ['sometimes', 'nullable', 'exists:barangays,id'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'is_accepting_bookings' => ['sometimes', 'boolean'],
        ];
    }
}
