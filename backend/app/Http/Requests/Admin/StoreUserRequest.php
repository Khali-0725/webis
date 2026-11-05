<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        if (! $actor?->isAdmin()) {
            return false;
        }

        return $this->input('role') !== UserRole::Admin->value || $actor->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^(\+?63|0)9\d{9}$/'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            // Super Admin accounts are provisioned outside the public API.
            'role' => ['required', Rule::in([UserRole::Client->value, UserRole::Provider->value, UserRole::Admin->value])],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
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
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'phone' => is_string($this->phone) ? preg_replace('/[\s\-()]/', '', $this->phone) : $this->phone,
        ]);
    }
}
