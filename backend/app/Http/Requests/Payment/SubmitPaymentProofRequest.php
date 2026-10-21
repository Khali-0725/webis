<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SubmitPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', File::types(config('webis.uploads.image_mimes'))->max(config('webis.uploads.image_max_kb'))],
            'reference_number' => ['required', 'string', 'max:100'],
            'declared_amount' => ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
