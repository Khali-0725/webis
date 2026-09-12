<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubmitPaymentProofRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not audience-namespaced: client, provider and admin all read/act on the
 * same `payments` resource, scoped entirely by PaymentPolicy - same shape as
 * BookingController and ConversationController.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $payment = $booking->payment()->with(['booking', 'paymentMethod', 'proofs'])->firstOrFail();

        return ApiResponse::ok(new PaymentResource($payment));
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

        if ($user->isProvider()) {
            $profile = $user->providerProfile()->firstOrFail();
            $query->where('provider_profile_id', $profile->id);
        } elseif ($user->isClient()) {
            $query->where('client_id', $user->id);
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated($query->orderByDesc('created_at')->paginate($perPage), PaymentResource::class);
    }
}
