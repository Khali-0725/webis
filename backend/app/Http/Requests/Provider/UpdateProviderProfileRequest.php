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
            'birthdate' => ['sometimes', 'nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'show_age_publicly' => ['sometimes', 'boolean'],
            'base_barangay_id' => ['sometimes', 'nullable', 'exists:barangays,id'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'is_accepting_bookings' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'birthdate.before_or_equal' => 'You must be at least 18 years old.',
        ];
    }
}
