<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only guests may sign in - same rule as RegisterRequest/LoginRequest.
        return $this->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'string'],

            // Optional: only the Register page sends it, to say which role a
            // brand-new account should get. Omitted (Login page) means "sign
            // in to an existing account only, never create one."
            'role' => ['nullable', 'string', Rule::in(UserRole::selfRegisterable())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Choose whether you are signing up as a client or a service provider.',
        ];
    }
}
