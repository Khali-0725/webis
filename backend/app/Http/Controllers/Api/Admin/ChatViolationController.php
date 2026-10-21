<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ViolationAdminStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ChatViolationResource;
use App\Models\ChatViolation;
use App\Support\AdminRecordFilters;
use App\Services\ChatViolationService;
use App\Services\AccountSuspensionService;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatViolationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->validate([
            'admin_status' => ['sometimes', Rule::in(ViolationAdminStatus::values())],
        ])['admin_status'] ?? null;

        // Default to the open queue - except in the trash, which shows every
        // deleted row regardless of its moderation status.
        if ($status === null && $request->string('trashed')->toString() !== 'only') {
            $status = ViolationAdminStatus::Open->value;
        }

        $violations = TrashFilter::apply(ChatViolation::query(), $request)
            ->with('user')
            ->when($status, fn ($query) => $query->where('admin_status', $status));

        $violations = AdminRecordFilters::apply($violations, $request)
            ->paginate(min($request->integer('per_page', 15), config('webis.pagination.max')));

        return ApiResponse::paginated($violations, ChatViolationResource::class);
    }

    /**
     * Per §9.3's migration comment: admin access to a flagged message is
     * itself audit-logged.
     */
    public function show(Request $request, ChatViolation $violation): JsonResponse
    {
        $violation->loadMissing('user');

        AuditLogger::record($request->user(), 'chat_violation.viewed', $violation, [], $request);

        return ApiResponse::ok(new ChatViolationResource($violation));
    }

    public function warn(Request $request, ChatViolation $violation, ChatViolationService $service): JsonResponse
    {
        return $this->act($request, $violation, $service, 'warn');
    }

    public function suspend(Request $request, ChatViolation $violation, ChatViolationService $service): JsonResponse
    {
        return $this->act($request, $violation, $service, 'suspend');
    }

    public function dismiss(Request $request, ChatViolation $violation, ChatViolationService $service): JsonResponse
    {
        return $this->act($request, $violation, $service, 'dismiss');
    }

    private function act(Request $request, ChatViolation $violation, ChatViolationService $service, string $action): JsonResponse
    {
        $suspension = $action === 'suspend' ? $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'basis' => ['required', 'string', 'max:120'],
            'days' => ['required', 'integer', 'between:1,'.AccountSuspensionService::maximumDays()],
        ]) : [];
        $violation = $service->action($violation, $request->user(), $action, $suspension, $request);

        AuditLogger::record($request->user(), "chat_violation.{$action}", $violation, $suspension, $request);

        return ApiResponse::ok(new ChatViolationResource($violation->load('user')), "Violation {$action}ed.");
    }

    /**
     * Soft delete - the row stays for the audit trail; it only leaves the
     * moderation queue. Restorable from the trash filter.
     */
    public function destroy(Request $request, ChatViolation $violation): JsonResponse
    {
        $violation->delete();

        AuditLogger::record($request->user(), 'chat_violation.deleted', $violation, [], $request);

        return ApiResponse::noContent('Violation moved to trash.');
    }

    public function restore(Request $request, ChatViolation $violation): JsonResponse
    {
        if (! $violation->trashed()) {
            throw DomainException::conflict('This violation is not deleted.');
        }

        $violation->restore();

        AuditLogger::record($request->user(), 'chat_violation.restored', $violation, [], $request);

        return ApiResponse::ok(new ChatViolationResource($violation->fresh('user')), 'Violation restored.');
    }
}
