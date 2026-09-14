<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StorePaymentMethodRequest;
use App\Http\Requests\Provider\UpdatePaymentMethodRequest;
use App\Http\Resources\ProviderPaymentMethodResource;
use App\Models\ProviderPaymentMethod;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $methods = $profile->paymentMethods()->orderByDesc('is_default')->get();

        return ApiResponse::ok(ProviderPaymentMethodResource::collection($methods));
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $data = $request->safe()->except('qr_image');

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $request->file('qr_image')->store('payment-qr', config('webis.uploads.disk'));
        }

        $method = DB::transaction(function () use ($profile, $data) {
            if (! empty($data['is_default'])) {
                $profile->paymentMethods()->update(['is_default' => false]);
            }

            return $profile->paymentMethods()->create($data);
        });

        // ->fresh(): DB-default columns we didn't pass (is_active) aren't
        // reflected on the in-memory model right after create() - same trap
        // documented for Booking/Conversation in earlier phases.
        return ApiResponse::created(new ProviderPaymentMethodResource($method->fresh()), 'Payment method added.');
    }

    public function update(UpdatePaymentMethodRequest $request, ProviderPaymentMethod $method): JsonResponse
    {
        $this->authorize('update', $method);

        $data = $request->safe()->except('qr_image');

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $request->file('qr_image')->store('payment-qr', config('webis.uploads.disk'));
        }

        DB::transaction(function () use ($method, $data) {
            if (! empty($data['is_default'])) {
                $method->providerProfile->paymentMethods()->where('id', '!=', $method->id)->update(['is_default' => false]);
            }

            $method->update($data);
        });

        return ApiResponse::ok(new ProviderPaymentMethodResource($method->fresh()), 'Payment method updated.');
    }

    public function toggle(Request $request, ProviderPaymentMethod $method): JsonResponse
    {
        $this->authorize('update', $method);

        $method->update(['is_active' => ! $method->is_active]);

        return ApiResponse::ok(new ProviderPaymentMethodResource($method->fresh()), 'Payment method updated.');
    }

    public function show(Request $request, ProviderPaymentMethod $method): JsonResponse
    {
        $this->authorize('update', $method);

        return ApiResponse::ok(new ProviderPaymentMethodResource($method));
    }

    /**
     * Soft delete. Refused while an online payment is still pending against
     * it - the client would lose the QR they're about to pay to. Settled
     * payments keep resolving it through Payment::paymentMethod()
     * ->withTrashed().
     */
    public function destroy(Request $request, ProviderPaymentMethod $method): JsonResponse
    {
        $this->authorize('delete', $method);

        $inFlight = $method->payments()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::ProofSubmitted->value])
            ->whereHas('booking', fn ($q) => $q->slotBlocking())
            ->exists();

        if ($inFlight) {
            throw DomainException::conflict('This payment method still has bookings waiting to be paid through it. Deactivate it instead until they settle.');
        }

        DB::transaction(function () use ($method) {
            $wasDefault = $method->is_default;
            $method->delete();

            // Promote another active method so online bookings keep working.
            if ($wasDefault) {
                $method->providerProfile->paymentMethods()->active()->orderBy('id')->first()?->update(['is_default' => true]);
            }
        });

        return ApiResponse::noContent('Payment method deleted.');
    }
}
