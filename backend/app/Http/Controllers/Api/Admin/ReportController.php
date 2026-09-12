<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->validate([
            'status' => ['sometimes', Rule::in(ReportStatus::values())],
        ])['status'] ?? null;

        $reports = Report::query()
            ->with('reporter', 'handledByUser')
            ->when($status, fn ($query) => $query->where('status', $status), fn ($query) => $query->open())
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated($reports, ReportResource::class);
    }

    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $report->forceFill([
            'status' => $request->validated('status'),
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
            'handling_notes' => $request->validated('handling_notes'),
        ])->save();

        AuditLogger::record($request->user(), 'report.resolved', $report, [
            'status' => $report->status->value,
        ], $request);

        return ApiResponse::ok(new ReportResource($report->fresh(['reporter', 'handledByUser'])), 'Report updated.');
    }
}
