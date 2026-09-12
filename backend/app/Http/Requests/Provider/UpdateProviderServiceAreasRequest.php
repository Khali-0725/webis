<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderServiceAreasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'barangay_ids' => ['present', 'array', 'max:41'],
            'barangay_ids.*' => ['integer', 'distinct', 'exists:barangays,id'],
        ];
    }
}
