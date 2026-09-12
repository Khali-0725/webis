<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120', Rule::unique('service_categories', 'name')->ignore($categoryId)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:140', Rule::unique('service_categories', 'slug')->ignore($categoryId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:min_price'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
