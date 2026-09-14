<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'details' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
