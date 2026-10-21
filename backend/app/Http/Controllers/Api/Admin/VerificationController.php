<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Enums\VerificationDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationDocumentResource;
use App\Http\Resources\Admin\VerificationProviderResource;
use App\Models\ProviderVerificationDocument;
use App\Models\ProviderProfile;
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
            'status' => ['nullable', Rule::in([...DocumentStatus::values(), 'all'])],
            'profile_status' => ['nullable', Rule::in(VerificationStatus::values())],
        ]);

        $query = ProviderVerificationDocument::with('providerProfile.user');
        if (($validated['status'] ?? 'pending') !== 'all') {
            $query->where('status', $validated['status'] ?? DocumentStatus::Pending->value);
        }
        if (! empty($validated['profile_status'])) {
            $query->whereHas('providerProfile', fn ($q) => $q->where('verification_status', $validated['profile_status']));
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate($perPage),
            VerificationDocumentResource::class,
            ['counts' => [
                'pending' => ProviderVerificationDocument::where('status', DocumentStatus::Pending->value)->count(),
                'verified' => ProviderProfile::where('verification_status', VerificationStatus::Approved->value)->count(),
                'unverified' => ProviderProfile::where('verification_status', VerificationStatus::Pending->value)->count(),
                'rejected' => ProviderProfile::where('verification_status', VerificationStatus::Rejected->value)->count(),
            ]],
        );
    }

    /** Overall provider status, including accounts that have not uploaded a document yet. */
    public function providers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(VerificationStatus::values())],
        ]);
        $perPage = min(max(1, $request->integer('per_page', config('webis.pagination.default'))), config('webis.pagination.max'));
        $query = ProviderProfile::query()
            ->where('verification_status', $validated['status'])
            ->with(['user', 'verificationDocuments']);

        return ApiResponse::paginated(
            $query->orderByDesc('created_at')->paginate($perPage),
            VerificationProviderResource::class,
        );
    }

    /** Approve one document; the provider still requires a separate final decision. */
    public function approve(Request $request, ProviderVerificationDocument $document): JsonResponse
    {
        DB::transaction(function () use ($request, $document) {
            $document = ProviderVerificationDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if ($document->status !== DocumentStatus::Pending) {
                throw \App\Exceptions\DomainException::conflict('Only a pending document can be approved.');
            }
            $document->forceFill([
                'status' => DocumentStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ])->save();
            AuditLogger::record($request->user(), 'verification.document_approved', $document, [], $request);
        });
        return ApiResponse::ok(null, 'Document approved. Final provider approval is still required.');
    }

    public function reject(Request $request, ProviderVerificationDocument $document): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $document, $validated) {
            $document = ProviderVerificationDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if ($document->status !== DocumentStatus::Pending) {
                throw \App\Exceptions\DomainException::conflict('Only a pending document can be rejected.');
            }
            $document->forceFill([
                'status' => DocumentStatus::Rejected,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['reason'] ?? null,
            ])->save();

            if ($this->isCurrentRequiredDocument($document)) {
                $document->providerProfile->forceFill([
                    'verification_status' => VerificationStatus::Rejected,
                    'verified_at' => null,
                    'verified_by' => null,
                    'rejection_reason' => $validated['reason'],
                ])->save();
            }
            AuditLogger::record($request->user(), 'verification.document_rejected', $document, ['reason' => $validated['reason']], $request);
        });

        return ApiResponse::ok(null, 'Document rejected.');
    }

    public function approveProvider(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        DB::transaction(function () use ($request, $providerProfile) {
            $profile = ProviderProfile::whereKey($providerProfile->id)->lockForUpdate()->firstOrFail();
            foreach ([VerificationDocumentType::GovernmentId, VerificationDocumentType::BusinessPermit] as $requiredType) {
                $currentDocument = $profile->verificationDocuments()
                    ->where('document_type', $requiredType->value)
                    ->orderByDesc('id')
                    ->first();
                if ($currentDocument?->status !== DocumentStatus::Approved) {
                    throw \App\Exceptions\DomainException::unprocessable("The latest {$requiredType->label()} must be approved before provider approval.");
                }
            }

            $profile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null,
            ])->save();
            AuditLogger::record($request->user(), 'verification.provider_approved', $profile, [], $request);
        });

        return ApiResponse::ok(null, 'Provider verified.');
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_ids' => ['required', 'array', 'min:1', 'max:100'],
            'document_ids.*' => ['required', 'integer', 'distinct', 'exists:provider_verification_documents,id'],
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:5', 'max:500'],
        ]);

        $processed = DB::transaction(function () use ($request, $data) {
            $documents = ProviderVerificationDocument::whereIn('id', $data['document_ids'])
                ->where('status', DocumentStatus::Pending->value)
                ->lockForUpdate()->get();

            foreach ($documents as $document) {
                $approve = $data['action'] === 'approve';
                $document->forceFill([
                    'status' => $approve ? DocumentStatus::Approved : DocumentStatus::Rejected,
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                    'review_notes' => $approve ? null : $data['reason'],
                ])->save();
                if (! $approve && $this->isCurrentRequiredDocument($document)) {
                    $document->providerProfile->forceFill([
                        'verification_status' => VerificationStatus::Rejected,
                        'verified_at' => null,
                        'verified_by' => null,
                        'rejection_reason' => $data['reason'],
                    ])->save();
                }
                AuditLogger::record($request->user(), $approve ? 'verification.document_approved' : 'verification.document_rejected', $document, ['reason' => $data['reason'] ?? null], $request);
            }

            return $documents->count();
        });

        return ApiResponse::ok(['processed' => $processed, 'skipped' => count($data['document_ids']) - $processed]);
    }

    private function isCurrentRequiredDocument(ProviderVerificationDocument $document): bool
    {
        if (! in_array($document->document_type, [
            VerificationDocumentType::GovernmentId,
            VerificationDocumentType::BusinessPermit,
        ], true)) {
            return false;
        }

        return $document->providerProfile->verificationDocuments()
            ->where('document_type', $document->document_type->value)
            ->orderByDesc('id')
            ->value('id') === $document->id;
    }
}
