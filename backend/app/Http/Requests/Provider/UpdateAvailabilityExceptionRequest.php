<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing an existing exception: the date is fixed (it is the row's
 * identity - UNIQUE(provider_profile_id, date)); only the hours/closed
 * flag/reason change. To move it to another day, delete and re-add.
 */
class UpdateAvailabilityExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'is_closed' => ['sometimes', 'required', 'boolean'],
            'start_time' => ['required_if:is_closed,false', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:is_closed,false', 'nullable', 'date_format:H:i', 'after:start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
