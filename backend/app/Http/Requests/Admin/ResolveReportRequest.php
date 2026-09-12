<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ReportStatus::Resolved->value, ReportStatus::Dismissed->value])],
            'handling_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
