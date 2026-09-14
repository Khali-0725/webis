<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
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

        $reports = TrashFilter::apply(Report::query(), $request)
            ->with('reporter', 'handledByUser')
            // Default to the open queue - except in the trash, which should
            // show everything that was deleted regardless of its status.
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when(! $status && $request->string('trashed')->toString() !== 'only', fn ($query) => $query->open())
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

    public function show(Report $report): JsonResponse
    {
        return ApiResponse::ok(new ReportResource($report->load(['reporter', 'handledByUser'])));
    }

    public function destroy(Request $request, Report $report): JsonResponse
    {
        $report->delete();

        AuditLogger::record($request->user(), 'report.deleted', $report, [], $request);

        return ApiResponse::noContent('Report moved to trash.');
    }

    public function restore(Request $request, Report $report): JsonResponse
    {
        if (! $report->trashed()) {
            throw DomainException::conflict('This report is not deleted.');
        }

        $report->restore();

        AuditLogger::record($request->user(), 'report.restored', $report, [], $request);

        return ApiResponse::ok(new ReportResource($report->fresh(['reporter', 'handledByUser'])), 'Report restored.');
    }
}
