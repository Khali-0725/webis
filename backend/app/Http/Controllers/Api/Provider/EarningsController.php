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
        $dates = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $profile = $request->user()->providerProfile()->firstOrFail();

        $payments = Payment::query()->where('provider_profile_id', $profile->id);
        $period = fn ($query, $column) => $query
            ->when($dates['from'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
            ->when($dates['to'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));

        $verified = $period((clone $payments)->where('status', PaymentStatus::Verified), 'verified_at');
        $pending = $period((clone $payments)->whereIn('status', [PaymentStatus::Pending, PaymentStatus::ProofSubmitted]), 'created_at');
        $rejected = $period((clone $payments)->where('status', PaymentStatus::Rejected), 'created_at');
        $refunded = $period((clone $payments)->where('status', PaymentStatus::Refunded), 'created_at');

        return ApiResponse::ok([
            'total_earnings' => (float) (clone $verified)->sum('amount'),
            'verified_count' => (clone $verified)->count(),
            'awaiting_review_count' => (clone $pending)->where('status', PaymentStatus::ProofSubmitted)->count(),
            'pending_count' => (clone $pending)->where('status', PaymentStatus::Pending)->count(),
            'pending_amount' => (float) (clone $pending)->sum('amount'),
            'rejected_amount' => (float) (clone $rejected)->sum('amount'),
            'refunded_amount' => (float) (clone $refunded)->sum('amount'),
            'provider_name' => $profile->business_name ?: $request->user()->full_name,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $dates = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $profile = $request->user()->providerProfile()->firstOrFail();
        $query = Payment::query()->where('provider_profile_id', $profile->id)
            ->where('status', PaymentStatus::Verified)
            ->with('booking.service');
        if (! empty($dates['from'])) $query->whereDate('verified_at', '>=', $dates['from']);
        if (! empty($dates['to'])) $query->whereDate('verified_at', '<=', $dates['to']);

        return ApiResponse::ok($query->orderByDesc('verified_at')->get()->map(fn (Payment $payment) => [
            'id' => $payment->id,
            'booking_code' => $payment->booking?->booking_code,
            'service' => $payment->booking?->service?->title,
            'amount' => $payment->amount,
            'verified_at' => $payment->verified_at?->toIso8601String(),
        ]));
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
