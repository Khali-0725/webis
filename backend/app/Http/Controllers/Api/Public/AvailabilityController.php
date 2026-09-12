<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\SlotGenerator;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly SlotGenerator $slots)
    {
    }

    public function show(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        if (! $providerProfile->isVerified() || ! $providerProfile->is_accepting_bookings) {
            return ApiResponse::ok([]);
        }

        $slots = $this->slots->freeSlots($providerProfile->id, $request->string('date')->toString());

        return ApiResponse::ok($slots);
    }
}
