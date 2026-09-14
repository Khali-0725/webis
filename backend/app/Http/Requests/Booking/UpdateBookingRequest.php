<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A client editing their own *pending* request: notes and/or a new slot.
 * Everything else about a booking (price, provider, service, status) is
 * derived server-side and never client-editable - see BookingPolicy::update.
 */
class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'scheduled_start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'client_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
