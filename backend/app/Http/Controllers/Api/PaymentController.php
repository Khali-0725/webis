<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubmitPaymentProofRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\AdminRecordFilters;
use App\Services\PaymentService;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not audience-namespaced: client, provider and admin all read/act on the
 * same `payments` resource, scoped entirely by PaymentPolicy - same shape as
 * BookingController and ConversationController.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $payment = $booking->payment()->with(['booking', 'paymentMethod', 'proofs'])->firstOrFail();

        return ApiResponse::ok(new PaymentResource($payment));
    }

    public function receipt(Request $request, Booking $booking): JsonResponse
    {
        $payment = $booking->payment()->with(['proofs', 'paymentMethod', 'booking.service', 'booking.client', 'booking.providerProfile.user'])->firstOrFail();
        $this->authorize('viewReceipt', $payment);
        $current = $payment->proofs->firstWhere('superseded_at', null);

        return ApiResponse::ok([
            'receipt_id' => 'WBR-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
            'booking_code' => $booking->booking_code,
            'service' => $booking->service?->title,
            'provider' => $booking->providerProfile?->business_name ?: $booking->providerProfile?->user?->full_name,
            'client' => $booking->client?->full_name,
            'amount_due' => $payment->amount,
            'amount_declared_paid' => $current?->declared_amount,
            'reference_number' => $current?->reference_number ?? $payment->reference_number,
            'payment_date' => $current?->paid_at?->toIso8601String(),
            'submitted_at' => $current?->created_at?->toIso8601String(),
            'payment_method' => $payment->settlement_method->value,
            'status' => $payment->status->value,
            'verified_at' => $payment->verified_at?->toIso8601String(),
        ]);
    }

    public function submitProof(SubmitPaymentProofRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('submitProof', $booking);

        $payment = $booking->payment()->firstOrFail();

        $updated = $this->payments->submitProof(
            $payment,
            $request->user(),
            $request->file('proof'),
            $request->validated('reference_number'),
            $request->validated('declared_amount'),
            $request->validated('paid_at'),
        );

        return ApiResponse::ok(new PaymentResource($updated->load(['booking', 'paymentMethod', 'proofs'])), 'Payment proof submitted.');
    }

    public function verify(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('verify', $payment);

        $updated = $this->payments->verify($payment, $request->user());

        return ApiResponse::ok(new PaymentResource($updated->load(['booking', 'paymentMethod', 'proofs'])), 'Payment verified.');
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('reject', $payment);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $updated = $this->payments->reject($payment, $request->user(), $validated['reason']);

        return ApiResponse::ok(new PaymentResource($updated->load(['booking', 'paymentMethod', 'proofs'])), 'Payment rejected.');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Payment::query()->with(['booking.service', 'paymentMethod']);

        if ($user->isAdmin()) {
            TrashFilter::apply($query, $request);
            AdminRecordFilters::apply($query, $request);
        }

        if ($user->isProvider()) {
            $profile = $user->providerProfile()->firstOrFail();
            $query->where('provider_profile_id', $profile->id);
        } elseif ($user->isClient()) {
            $query->where('client_id', $user->id);
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        if (! $user->isAdmin()) {
            $query->orderByDesc('created_at');
        }

        return ApiResponse::paginated($query->paginate($perPage), PaymentResource::class);
    }

    /**
     * Admin-only soft delete (PaymentPolicy::delete). The row, its proofs
     * and the booking it belongs to all stay - it just leaves the ledger
     * until restored, and both actions are audit-logged.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        AuditLogger::record($request->user(), 'payment.deleted', $payment, [], $request);

        return ApiResponse::noContent('Payment moved to trash.');
    }

    public function restore(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('restore', $payment);

        if (! $payment->trashed()) {
            throw DomainException::conflict('This payment is not deleted.');
        }

        $payment->restore();

        AuditLogger::record($request->user(), 'payment.restored', $payment, [], $request);

        return ApiResponse::ok(new PaymentResource($payment->fresh(['booking', 'paymentMethod', 'proofs'])), 'Payment restored.');
    }
}
