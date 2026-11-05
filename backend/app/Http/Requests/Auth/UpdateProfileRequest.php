<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^(\+?63|0)9\d{9}$/'],
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['first_name', 'last_name', 'email', 'address_line'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        if (isset($values['email'])) {
            $values['email'] = mb_strtolower($values['email']);
        }
        if ($this->has('phone') && is_string($this->input('phone'))) {
            $values['phone'] = preg_replace('/[\s\-()]/', '', $this->input('phone'));
        }
        $this->merge($values);
    }
}
