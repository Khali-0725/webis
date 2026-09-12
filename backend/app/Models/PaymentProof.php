<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * History of uploaded payment proofs for a payment.
 *
 * A rejected proof is superseded, not destroyed.
 */
class PaymentProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'reference_number',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'superseded_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeCurrent($query)
    {
        return $query->whereNull('superseded_at');
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
