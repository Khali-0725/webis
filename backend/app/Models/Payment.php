<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SettlementMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One payment per booking (UNIQUE booking_id).
 *
 * VERIFIED means a human reviewed the uploaded proof — nothing here
 * pretends a bank transferred money. Soft-deleted, unlike most of this
 * codebase (which prefers a status/visibility flag over deletion) - a
 * financial record is the one thing here that must never actually
 * disappear, even if something later needs to remove it from normal views.
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_profile_id',
        'provider_payment_method_id',
        'settlement_method',
        'amount',
        'currency',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'settlement_method' => SettlementMethod::class,
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
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class)->withTrashed();
    }

    public function paymentMethod(): BelongsTo
    {
        // withTrashed(): a provider deleting a payment method must not blank out
        // the method on payments that already settled through it.
        return $this->belongsTo(ProviderPaymentMethod::class, 'provider_payment_method_id')->withTrashed();
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
