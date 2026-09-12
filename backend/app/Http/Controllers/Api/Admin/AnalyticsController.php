<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * §18 "Metrics"/breakdowns. Every query here is a single GROUP BY/count in
 * SQL - never a full-table fetch resolved in PHP.
 */
class AnalyticsController extends Controller
{
    public function summary(): JsonResponse
    {
        return ApiResponse::ok([
            'users' => [
                'total' => User::query()->count(),
                'clients' => User::query()->where('role', UserRole::Client)->count(),
                'providers' => User::query()->where('role', UserRole::Provider)->count(),
            ],
            'providers' => [
                'verified' => ProviderProfile::query()->where('verification_status', VerificationStatus::Approved)->count(),
                'pending' => ProviderProfile::query()->where('verification_status', VerificationStatus::Pending)->count(),
                'rejected' => ProviderProfile::query()->where('verification_status', VerificationStatus::Rejected)->count(),
            ],
            'services' => [
                'total' => Service::query()->count(),
                'active' => Service::query()->where('is_active', true)->count(),
            ],
            'bookings' => [
                'total' => Booking::query()->count(),
                'pending' => Booking::query()->where('status', BookingStatus::Pending)->count(),
                'completed' => Booking::query()->where('status', BookingStatus::Completed)->count(),
                'cancelled' => Booking::query()->where('status', BookingStatus::Cancelled)->count(),
                'disputed' => Booking::query()->where('status', BookingStatus::Disputed)->count(),
            ],
            'payments' => [
                'total' => Payment::query()->count(),
                'verified' => Payment::query()->where('status', PaymentStatus::Verified)->count(),
                'awaiting_review' => Payment::query()->where('status', PaymentStatus::ProofSubmitted)->count(),
                'total_earnings' => (float) Payment::query()->where('status', PaymentStatus::Verified)->sum('amount'),
            ],
        ]);
    }

    public function bookingsOverTime(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $rows = Booking::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return ApiResponse::ok($rows);
    }

    /**
     * Platform earnings (verified payments only - a submitted-but-unverified
     * proof is not revenue) grouped by the day the provider/admin verified
     * it, not the day the booking was made.
     */
    public function earningsOverTime(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $rows = Payment::query()
            ->selectRaw('DATE(verified_at) as date, COALESCE(SUM(amount), 0) as total')
            ->where('status', PaymentStatus::Verified)
            ->whereBetween('verified_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return ApiResponse::ok($rows);
    }

    public function bookingsByCategory(): JsonResponse
    {
        $rows = DB::table('bookings')
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->join('service_categories', 'service_categories.id', '=', 'services.service_category_id')
            ->selectRaw('service_categories.name as category, COUNT(*) as total')
            ->groupBy('service_categories.name')
            ->orderByDesc('total')
            ->get();

        return ApiResponse::ok($rows);
    }

    public function topProviders(): JsonResponse
    {
        $byRating = ProviderProfile::query()
            ->with('user:id,full_name')
            ->where('rating_count', '>=', 3)
            ->orderByDesc('rating_avg')
            ->limit(10)
            ->get(['id', 'user_id', 'business_name', 'rating_avg', 'rating_count']);

        $byCompletedBookings = ProviderProfile::query()
            ->with('user:id,full_name')
            ->orderByDesc('completed_bookings_count')
            ->limit(10)
            ->get(['id', 'user_id', 'business_name', 'completed_bookings_count']);

        return ApiResponse::ok([
            'by_rating' => $byRating,
            'by_completed_bookings' => $byCompletedBookings,
        ]);
    }

    public function paymentSummary(): JsonResponse
    {
        $rows = Payment::query()
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount_sum')
            ->groupBy('status')
            ->get();

        return ApiResponse::ok($rows);
    }
}
