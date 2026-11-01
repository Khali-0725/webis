<?php

namespace App\Http\Requests\Booking;

use App\Enums\SettlementMethod;
use App\Models\Barangay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('barangay_id') || $validator->errors()->has('latitude') || $validator->errors()->has('longitude')) {
                return;
            }

            $barangay = Barangay::query()->active()->find($this->input('barangay_id'));
            if (! $barangay || $barangay->latitude === null || $barangay->longitude === null) {
                $validator->errors()->add('barangay_id', 'Select an active barangay with a map center.');

                return;
            }

            $latitude = deg2rad((float) $this->input('latitude'));
            $longitude = deg2rad((float) $this->input('longitude'));
            $centerLatitude = deg2rad((float) $barangay->latitude);
            $centerLongitude = deg2rad((float) $barangay->longitude);
            $haversine = sin(($latitude - $centerLatitude) / 2) ** 2
                + cos($centerLatitude) * cos($latitude) * sin(($longitude - $centerLongitude) / 2) ** 2;
            $distanceKm = 6371 * 2 * asin(sqrt(min(1, $haversine)));

            if ($distanceKm > 2) {
                $validator->errors()->add('latitude', 'Place the pin within 2 km of the selected barangay.');
            }
        }];
    }
}
