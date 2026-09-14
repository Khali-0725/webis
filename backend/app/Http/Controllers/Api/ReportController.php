<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The reporter's side of reports: file one, see their own, and edit or
 * withdraw one that admin hasn't picked up yet (ReportPolicy). Admin
 * handling lives in Admin\ReportController.
 */
class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max')));

        return ApiResponse::paginated($reports, ReportResource::class);
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return ApiResponse::ok(new ReportResource($report));
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $request->reportableModelClass(),
            'reportable_id' => $request->validated('reportable_id'),
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
        ]);

        return ApiResponse::created(new ReportResource($report->fresh()), 'Report submitted.');
    }

    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        return ApiResponse::ok(new ReportResource($report->fresh()), 'Report updated.');
    }

    public function destroy(Request $request, Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return ApiResponse::noContent('Report withdrawn.');
    }
}
