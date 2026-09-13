<?php

namespace App\Http\Requests\Booking;

use App\Enums\SettlementMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'address_line' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'landmark_notes' => ['nullable', 'string', 'max:1000'],
            'client_notes' => ['nullable', 'string', 'max:1000'],
            'settlement_method' => ['required', Rule::in(SettlementMethod::values())],
        ];
    }
}
