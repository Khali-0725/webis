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
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
