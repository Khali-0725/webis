<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationDocumentResource;
use App\Models\ProviderVerificationDocument;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(DocumentStatus::values())],
        ]);

        $query = ProviderVerificationDocument::with('providerProfile.user')
            ->where('status', $validated['status'] ?? DocumentStatus::Pending->value);

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate($perPage),
            VerificationDocumentResource::class,
        );
    }

    /**
     * Approving a document also verifies the provider profile it belongs to -
     * without this the provider could never publish a service, since
     * ServiceController::publish() requires verification_status = Approved.
     */
    public function approve(Request $request, ProviderVerificationDocument $document): JsonResponse
    {
        DB::transaction(function () use ($request, $document) {
            $document->forceFill([
                'status' => DocumentStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ])->save();

            $document->providerProfile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null,
            ])->save();
        });

        AuditLogger::record($request->user(), 'verification.approved', $document, [], $request);

        return ApiResponse::ok(null, 'Document approved.');
    }

    public function reject(Request $request, ProviderVerificationDocument $document): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $document, $validated) {
            $document->forceFill([
                'status' => DocumentStatus::Rejected,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['reason'] ?? null,
            ])->save();

            $document->providerProfile->forceFill([
                'verification_status' => VerificationStatus::Rejected,
                'rejection_reason' => $validated['reason'] ?? 'Submitted document was rejected.',
            ])->save();
        });

        AuditLogger::record($request->user(), 'verification.rejected', $document, ['reason' => $validated['reason'] ?? null], $request);

        return ApiResponse::ok(null, 'Document rejected.');
    }
}
