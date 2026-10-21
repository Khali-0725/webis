<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use App\Support\AdminRecordFilters;
use App\Notifications\AccountEventNotification;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when(! $status && $request->string('trashed')->toString() !== 'only', fn ($query) => $query->open());

        $reports = AdminRecordFilters::apply($reports, $request)
            ->paginate(min($request->integer('per_page', 15), config('webis.pagination.max')));

        return ApiResponse::paginated($reports, ReportResource::class);
    }

    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $report = DB::transaction(function () use ($request, $report) {
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($locked->status->isClosed() || ($locked->status === ReportStatus::Reviewing && $request->validated('status') === ReportStatus::Reviewing->value)) {
                throw DomainException::conflict('This report has already been reviewed or closed. Refresh the list.');
            }
            $locked->forceFill([
                'status' => $request->validated('status'),
                'handled_by' => $request->user()->id,
                'handled_at' => now(),
                'handling_notes' => $request->validated('handling_notes'),
            ])->save();

            AuditLogger::record($request->user(), $locked->status === ReportStatus::Reviewing ? 'report.acknowledged' : 'report.resolved', $locked, [
                'status' => $locked->status->value,
            ], $request);

            return $locked;
        });
        $audience = $report->reporter?->isProvider() ? 'provider' : 'client';
        $report->reporter?->notify(new AccountEventNotification(
            $report->status === ReportStatus::Reviewing ? 'report.acknowledged' : 'report.status_updated',
            "Your report status is now {$report->status->label()}.",
            "/{$audience}/reports",
        ));

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
