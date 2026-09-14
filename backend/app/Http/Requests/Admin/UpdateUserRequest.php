<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^(\+?63|0)9\d{9}$/'],
            'barangay_id' => ['sometimes', 'nullable', 'exists:barangays,id'],
            'role' => ['sometimes', Rule::in(UserRole::values())],
            'password' => ['sometimes', 'nullable', 'string', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Philippine mobile number (e.g. 09171234567).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->email)) {
            $merge['email'] = mb_strtolower(trim($this->email));
        }

        if (is_string($this->phone)) {
            $merge['phone'] = preg_replace('/[\s\-()]/', '', $this->phone);
        }

        $this->merge($merge);
    }
}
