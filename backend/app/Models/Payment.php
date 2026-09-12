<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One payment per booking (UNIQUE booking_id).
 *
 * VERIFIED means a human reviewed the uploaded proof — nothing here
 * pretends a bank transferred money.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_profile_id',
        'provider_payment_method_id',
        'amount',
        'currency',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', array_map(
            fn (PaymentStatus $s) => $s->value,
            PaymentStatus::awaitingReview()
        ));
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(ProviderPaymentMethod::class, 'provider_payment_method_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->orderByDesc('created_at');
    }

    /**
     * The current (non-superseded) proof.
     */
    public function currentProof(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->whereNull('superseded_at')->latest();
    }
}
