<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The manual QR-proof settlement workflow (§16 / R-33) - no payment gateway,
 * a human always makes the verify/reject call. The one rule enforced here
 * that the schema itself does not: once a payment is Verified or Refunded,
 * it is immutable - no new proof, no re-verification.
 */
class PaymentService
{
    public function submitProof(Payment $payment, User $client, UploadedFile $file, ?string $referenceNumber): Payment
    {
        return DB::transaction(function () use ($payment, $client, $file, $referenceNumber) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isSettled()) {
                throw DomainException::conflict('This payment has already been settled and can no longer be changed.');
            }

            $locked->proofs()->whereNull('superseded_at')->update(['superseded_at' => now()]);

            $path = $file->store('payment-proofs', config('webis.uploads.disk'));

            PaymentProof::create([
                'payment_id' => $locked->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'reference_number' => $referenceNumber,
                'uploaded_by' => $client->id,
            ]);

            $attributes = ['status' => PaymentStatus::ProofSubmitted];

            if ($referenceNumber) {
                $attributes['reference_number'] = $referenceNumber;
            }

            $locked->forceFill($attributes)->save();

            return $locked->fresh(['proofs', 'paymentMethod']);
        });
    }

    public function verify(Payment $payment, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::ProofSubmitted) {
                throw DomainException::conflict('Only a payment with a submitted proof can be verified.');
            }

            $locked->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $locked->fresh();
        });
    }

    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::ProofSubmitted) {
                throw DomainException::conflict('Only a payment with a submitted proof can be rejected.');
            }

            $locked->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $actor->id,
                'rejection_reason' => $reason,
            ])->save();

            return $locked->fresh();
        });
    }
}
