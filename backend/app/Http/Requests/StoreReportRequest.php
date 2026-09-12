<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * `reportable_type` is an unconstrained polymorphic string column -
     * this allowlist is the only thing stopping a caller from pointing a
     * report at an arbitrary model class.
     */
    private const ALLOWED_TYPES = [
        'user' => User::class,
        'service' => Service::class,
        'booking' => Booking::class,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', Rule::in(array_keys(self::ALLOWED_TYPES))],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(ReportReason::values())],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function reportableModelClass(): string
    {
        return self::ALLOWED_TYPES[$this->validated('reportable_type')];
    }
}
