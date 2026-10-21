<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\ChatViolation;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Services\ReviewService;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Atomic bulk archive for records that have no additional delete preconditions. */
class BulkArchiveController extends Controller
{
    private const TYPES = [
        'services' => [Service::class, 'service.deleted'],
        'providers' => [ProviderProfile::class, 'provider.deleted'],
        'reports' => [Report::class, 'report.deleted'],
        'chat-violations' => [ChatViolation::class, 'chat_violation.deleted'],
        'reviews' => [Review::class, 'review.archived'],
    ];

    public function __invoke(Request $request, string $resource, ReviewService $reviews): JsonResponse
    {
        if (! isset(self::TYPES[$resource])) {
            abort(404);
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'reason' => [$resource === 'reviews' ? 'required' : 'nullable', 'string', 'min:5', 'max:500'],
        ]);

        [$model, $action] = self::TYPES[$resource];
        $count = DB::transaction(function () use ($model, $action, $data, $request, $resource, $reviews): int {
            $records = $model::query()->whereIn('id', $data['ids'])->orderBy('id')->lockForUpdate()->get();
            if ($records->count() !== count($data['ids'])) {
                throw DomainException::conflict('One or more selected records are no longer eligible. Refresh the list and try again.');
            }

            foreach ($records as $record) {
                if ($resource === 'reviews') {
                    $reviews->setVisibility($record, false);
                }
                $record->delete();
                AuditLogger::record($request->user(), $action, $record, [
                    'bulk' => true,
                    ...($resource === 'reviews' ? ['reason' => $data['reason']] : []),
                ], $request);
            }

            return $records->count();
        });

        return ApiResponse::ok(['archived_count' => $count], "{$count} records moved to the archive.");
    }
}
