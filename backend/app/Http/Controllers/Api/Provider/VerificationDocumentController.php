<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\VerificationDocumentType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProviderVerificationDocument;
use App\Models\ProviderProfile;
use App\Http\Resources\VerificationDocumentResource;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class VerificationDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        return ApiResponse::ok([
            'verification_status' => $profile->verification_status->value,
            'rejection_reason' => $profile->rejection_reason,
            'documents' => VerificationDocumentResource::collection(
                $profile->verificationDocuments()->with('providerProfile.user')->orderByDesc('id')->get()
            )->resolve(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => ['required', 'string', Rule::in(VerificationDocumentType::values())],
            'document' => ['required', File::types(config('webis.uploads.document_mimes'))->max(config('webis.uploads.document_max_kb'))],
        ]);

        $profile = $request->user()->providerProfile;

        if (!$profile) {
            return ApiResponse::error('Provider profile not found.', [], 404);
        }

        $disk = config('webis.uploads.disk');
        $path = $request->file('document')->store('documents', $disk);
        try {
            $document = DB::transaction(function () use ($request, $profile, $path) {
                $locked = ProviderProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
                $document = $locked->verificationDocuments()->create([
                    'document_type' => $request->document_type,
                    'file_path' => $path,
                    'original_name' => $request->file('document')->getClientOriginalName(),
                    'mime_type' => $request->file('document')->getMimeType(),
                    'size_bytes' => $request->file('document')->getSize(),
                ]);

                $required = in_array($request->document_type, [
                    VerificationDocumentType::GovernmentId->value,
                    VerificationDocumentType::BusinessPermit->value,
                ], true);
                if ($required && $locked->verification_status !== VerificationStatus::Pending) {
                    $locked->forceFill([
                        'verification_status' => VerificationStatus::Pending,
                        'verified_at' => null,
                        'verified_by' => null,
                        'rejection_reason' => null,
                    ])->save();
                    AuditLogger::record($request->user(), 'verification.provider_review_reopened', $locked, [
                        'document_id' => $document->id,
                    ], $request);
                }
                AuditLogger::record($request->user(), 'verification.document_uploaded', $document, [], $request);

                return $document;
            });
        } catch (\Throwable $error) {
            Storage::disk($disk)->delete($path);
            throw $error;
        }

        return ApiResponse::ok(new VerificationDocumentResource($document->fresh()->load('providerProfile.user')), 'Document uploaded successfully.');
    }
}
