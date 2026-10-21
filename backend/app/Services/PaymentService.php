<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\BookingStatus;
use App\Enums\SettlementMethod;
use App\Exceptions\DomainException;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use App\Notifications\AccountEventNotification;
use App\Support\Realtime;
use App\Support\AuditLogger;
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
    public function submitProof(Payment $payment, User $client, UploadedFile $file, string $referenceNumber, string $declaredAmount, ?string $paidAt): Payment
    {
        $updated = DB::transaction(function () use ($payment, $client, $file, $referenceNumber, $declaredAmount, $paidAt) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isSettled()) {
                throw DomainException::conflict('This payment has already been settled and can no longer be changed.');
            }

            if ($locked->settlement_method === SettlementMethod::Cash) {
                throw DomainException::unprocessable('This booking is settled in cash - no proof is needed.');
            }

            if (! in_array($locked->booking->status, [BookingStatus::Accepted, BookingStatus::InProgress], true)) {
                throw DomainException::conflict('Payment proof can only be submitted for an accepted or in-progress booking.');
            }

            if (! in_array($locked->status, [PaymentStatus::Pending, PaymentStatus::Rejected], true)) {
                throw DomainException::conflict('This proof is already under review. Wait for a decision before resubmitting.');
            }

            if ($this->cents($declaredAmount) !== $this->cents($locked->amount)) {
                throw DomainException::unprocessable('Declared amount must equal the booking amount. Contact the provider about any difference.', [
                    'declared_amount' => ['The declared amount does not match the booking amount.'],
                ]);
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
                'declared_amount' => $declaredAmount,
                'paid_at' => $paidAt,
                'uploaded_by' => $client->id,
            ]);

            $attributes = ['status' => PaymentStatus::ProofSubmitted];

            $attributes['reference_number'] = $referenceNumber;

            $locked->forceFill($attributes)->save();
            AuditLogger::record($client, 'payment.proof_submitted', $locked, ['proof_id' => $locked->proofs()->latest('id')->value('id')]);

            return $locked->fresh(['proofs', 'paymentMethod']);
        });

        $this->pushChange($updated);
        $providerUser = $updated->providerProfile?->user;
        $providerUser?->notify(new AccountEventNotification(
            'payment.proof_submitted',
            "Payment proof for booking {$updated->booking->booking_code} is awaiting review.",
            "/provider/bookings/{$updated->booking_id}",
        ));
        User::where('role', UserRole::Admin->value)->where('status', 'active')->get()
            ->each(fn (User $admin) => $admin->notify(new AccountEventNotification(
                'payment.proof_submitted',
                "A payment proof for booking {$updated->booking->booking_code} is awaiting review.",
                '/admin/payments',
            )));

        return $updated;
    }

    public function verify(Payment $payment, User $actor): Payment
    {
        $updated = DB::transaction(function () use ($payment, $actor) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Cash never has a proof to submit - the provider confirms
            // receipt directly from Pending. Online still requires the
            // client's proof to have been submitted first.
            $requiredStatus = $locked->settlement_method === SettlementMethod::Cash
                ? PaymentStatus::Pending
                : PaymentStatus::ProofSubmitted;

            if ($locked->status !== $requiredStatus) {
                throw DomainException::conflict(
                    $locked->settlement_method === SettlementMethod::Cash
                        ? 'Only a pending cash payment can be verified.'
                        : 'Only a payment with a submitted proof can be verified.'
                );
            }

            if (! in_array($locked->booking->status, [BookingStatus::Accepted, BookingStatus::InProgress, BookingStatus::Completed], true)) {
                throw DomainException::conflict('A payment for a pending, cancelled, or rejected booking cannot be verified.');
            }

            $locked->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ])->save();
            AuditLogger::record($actor, 'payment.verified', $locked);

            return $locked->fresh();
        });

        $this->pushChange($updated);
        $updated->client?->notify(new AccountEventNotification(
            'payment.verified',
            "Payment for booking {$updated->booking->booking_code} was verified.",
            "/client/bookings/{$updated->booking_id}",
        ));

        return $updated;
    }

    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        $updated = DB::transaction(function () use ($payment, $actor, $reason) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::ProofSubmitted) {
                throw DomainException::conflict('Only a payment with a submitted proof can be rejected.');
            }

            $locked->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $actor->id,
                'rejection_reason' => $reason,
            ])->save();
            AuditLogger::record($actor, 'payment.rejected', $locked, ['reason' => $reason]);

            return $locked->fresh();
        });

        $this->pushChange($updated);
        $updated->client?->notify(new AccountEventNotification(
            'payment.rejected',
            "Payment proof for booking {$updated->booking->booking_code} needs another review. Check the booking for details.",
            "/client/bookings/{$updated->booking_id}",
        ));

        return $updated;
    }

    private function pushChange(Payment $payment): void
    {
        Realtime::push(
            [$payment->client_id, $payment->providerProfile?->user_id],
            'payments',
            ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id],
        );
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
