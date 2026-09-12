<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'skills' => ['present', 'array', 'max:20'],
            'skills.*' => ['string', 'min:2', 'max:100', 'distinct:ignore_case'],
        ];
    }
}
