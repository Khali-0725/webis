<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAvailabilityRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'rules' => ['present', 'array', 'max:7'],
            'rules.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i'],
            'rules.*.slot_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('rules', []) as $index => $rule) {
                if (isset($rule['start_time'], $rule['end_time']) && $rule['end_time'] <= $rule['start_time']) {
                    $validator->errors()->add("rules.$index.end_time", 'End time must be after start time.');
                }
            }
        });
    }
}
