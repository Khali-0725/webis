<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\VerificationDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documents a provider uploads for verification (clearances, certificates, …).
 *
 * Files live on the private disk and are served only through a policy-checked
 * file controller — file_path is never a public URL.
 */
class ProviderVerificationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => VerificationDocumentType::class,
            'status' => DocumentStatus::class,
            'size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
