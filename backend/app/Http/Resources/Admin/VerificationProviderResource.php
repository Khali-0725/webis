<?php

namespace App\Http\Resources\Admin;

use App\Enums\VerificationDocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $documents = $this->verificationDocuments->sortByDesc('id')->values();
        $required = [];
        foreach ([VerificationDocumentType::GovernmentId, VerificationDocumentType::BusinessPermit] as $type) {
            $current = $documents->first(fn ($document) => $document->document_type === $type);
            $required[$type->value] = $current?->status->value ?? 'missing';
        }

        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'owner_name' => $this->user?->full_name,
            'verification_status' => $this->verification_status->value,
            'rejection_reason' => $this->rejection_reason,
            'required_documents' => $required,
            'documents' => $documents->map(fn ($document) => [
                'id' => $document->id,
                'document_type' => $document->document_type->value,
                'original_name' => $document->original_name,
                'status' => $document->status->value,
                'preview_url' => '/api/files/verification-document/'.$document->id,
            ])->all(),
        ];
    }
}
