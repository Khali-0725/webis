<?php

namespace App\Http\Requests\Provider;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProvider() ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(PaymentMethodType::values())],
            'account_name' => ['sometimes', 'string', 'max:150'],
            'account_ref_masked' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['sometimes', 'boolean'],
            // Optional on update - an already-uploaded QR stays unless a new one is sent.
            'qr_image' => ['nullable', File::types(config('webis.uploads.image_mimes'))->max(config('webis.uploads.image_max_kb'))],
        ];
    }
}
