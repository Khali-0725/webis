<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A provider's own earnings - the same shape as
 * Admin\AnalyticsController::summary()/earningsOverTime(), but scoped to
 * the caller's own provider_profile_id. Never accepts an id from the
 * client: the profile is always the signed-in user's own.
 */
class EarningsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        $payments = Payment::query()->where('provider_profile_id', $profile->id);

        return ApiResponse::ok([
            'total_earnings' => (float) (clone $payments)->where('status', PaymentStatus::Verified)->sum('amount'),
            'verified_count' => (clone $payments)->where('status', PaymentStatus::Verified)->count(),
            'awaiting_review_count' => (clone $payments)->where('status', PaymentStatus::ProofSubmitted)->count(),
            'pending_count' => (clone $payments)->where('status', PaymentStatus::Pending)->count(),
        ]);
    }

    public function overTime(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $rows = Payment::query()
            ->selectRaw('DATE(verified_at) as date, COALESCE(SUM(amount), 0) as total')
            ->where('provider_profile_id', $profile->id)
            ->where('status', PaymentStatus::Verified)
            ->whereBetween('verified_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return ApiResponse::ok($rows);
    }
}
