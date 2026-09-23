<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'role_title' => ['required', 'string', 'max:150'],
            'employer_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on', 'before_or_equal:today'],
        ];
    }
}
