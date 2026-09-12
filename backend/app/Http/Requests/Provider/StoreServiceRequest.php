<?php

namespace App\Http\Requests\Provider;

use App\Enums\PricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'pricing_type' => ['required', Rule::in(PricingType::values())],
            'price' => ['required_if:pricing_type,fixed,hourly', 'nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
        ];
    }
}
