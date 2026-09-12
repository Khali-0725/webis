<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\VerificationDocumentType;
use App\Http\Controllers\Controller;
use App\Models\ProviderVerificationDocument;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class VerificationDocumentController extends Controller
{
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

        $path = $request->file('document')->store('documents', config('webis.uploads.disk'));

        $document = $profile->verificationDocuments()->create([
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_name' => $request->file('document')->getClientOriginalName(),
            'mime_type' => $request->file('document')->getMimeType(),
            'size_bytes' => $request->file('document')->getSize(),
        ]);

        return ApiResponse::ok($document, 'Document uploaded successfully.');
    }
}
