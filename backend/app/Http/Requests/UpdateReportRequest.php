<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The reporter may only change *what they said* - the reported subject is
 * fixed at submission (file a new report to point at something else).
 */
class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'required', Rule::in(ReportReason::values())],
            'details' => ['sometimes', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('reason') === ReportReason::NonPayment->value
                && (! $this->user()->isProvider() || $this->route('report')?->reportable_type !== Booking::class)) {
                $validator->errors()->add('reason', 'Non-payment reports require your own provider booking.');
            }
        }];
    }
}
