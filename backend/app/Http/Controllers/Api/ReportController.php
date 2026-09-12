<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Support\Api\ApiResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request)
    {
        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $request->reportableModelClass(),
            'reportable_id' => $request->validated('reportable_id'),
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
        ]);

        return ApiResponse::created(['id' => $report->id], 'Report submitted.');
    }
}
