<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:3', 'max:255'],
            'answer' => ['required', 'string', 'min:3', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
