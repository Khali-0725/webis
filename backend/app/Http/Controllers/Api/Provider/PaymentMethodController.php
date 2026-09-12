<?php

namespace App\Http\Controllers\Api\Provider;

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
}
