<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only guests may register.
        return $this->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            // `rfc` only, deliberately: `dns` makes registration depend on a live
            // DNS lookup, which fails offline and rejects freshly-registered domains.
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^(\+?63|0)9\d{9}$/'],

            // A visitor may register only as a client or a provider.
            // "admin" is rejected here AND ignored by the service layer.
            'role' => ['required', 'string', Rule::in(UserRole::selfRegisterable())],

            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],

            'accepted_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Philippine mobile number (e.g. 09171234567).',
            'role.in' => 'Choose whether you are signing up as a client or a service provider.',
            'accepted_terms.accepted' => 'You must accept the terms of use to create an account.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'accepted_terms' => 'terms of use',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'first_name' => is_string($this->first_name) ? trim($this->first_name) : $this->first_name,
            'last_name' => is_string($this->last_name) ? trim($this->last_name) : $this->last_name,
            'phone' => is_string($this->phone) ? preg_replace('/[\s\-()]/', '', $this->phone) : $this->phone,
        ]);
    }
}
