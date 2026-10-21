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
            'status' => ['required', Rule::in([ReportStatus::Reviewing->value, ReportStatus::Resolved->value, ReportStatus::Dismissed->value])],
            'handling_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
