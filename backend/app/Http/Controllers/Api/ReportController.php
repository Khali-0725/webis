<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AccountEventNotification;
use App\Support\Api\ApiResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        try {
            $report = DB::transaction(function () use ($request): Report {
                $type = $request->reportableModelClass();
                $target = $type === User::class ? User::withTrashed() : $type::query();
                $target = $target->whereKey($request->validated('reportable_id'))->lockForUpdate()->first();
                if (! $target) {
                    throw DomainException::unprocessable('This record is not available for your report.');
                }

                $targetUserId = Report::targetUserIdFor($target, $request->user());
                if (! $targetUserId || $targetUserId === $request->user()->id) {
                    throw DomainException::unprocessable('This record is not available for your report.');
                }
                if (Report::withTrashed()->where('reporter_id', $request->user()->id)
                    ->where('target_user_id', $targetUserId)->exists()) {
                    throw DomainException::conflict('You have already reported this person.');
                }

                return Report::create([
                    'reporter_id' => $request->user()->id,
                    'target_user_id' => $targetUserId,
                    'reportable_type' => $type,
                    'reportable_id' => $request->validated('reportable_id'),
                    'reason' => $request->validated('reason'),
                    'details' => $request->validated('details'),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw DomainException::conflict('You have already reported this person.');
        }
        User::where('role', UserRole::Admin->value)->where('status', 'active')->get()
            ->each(fn (User $admin) => $admin->notify(new AccountEventNotification(
                'report.submitted', 'A new report was submitted for review.', '/admin/reports',
            )));
        $audience = $request->user()->isProvider() ? 'provider' : 'client';
        $request->user()->notify(new AccountEventNotification(
            'report.received', 'Your report was submitted for administrator review.', "/{$audience}/reports",
        ));

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
