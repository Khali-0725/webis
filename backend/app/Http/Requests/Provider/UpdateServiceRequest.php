<?php

namespace App\Http\Requests\Provider;

use App\Enums\PricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'service_category_id' => ['sometimes', 'exists:service_categories,id'],
            'title' => ['sometimes', 'string', 'min:5', 'max:150'],
            'description' => ['sometimes', 'string', 'min:20', 'max:5000'],
            'pricing_type' => ['sometimes', Rule::in(PricingType::values())],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:min_price'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:1440'],
        ];
    }
}
