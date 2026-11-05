<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Report;
use Illuminate\Http\Request;

class ProviderReportThreshold
{
    /** @return array{eligible: bool, reporting_clients: int, completed_clients: int}|null */
    public function forTargetUserId(int $userId, Request $request): ?array
    {
        $cache = $request->attributes->get('provider_report_thresholds', []);
        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        $profileId = ProviderProfile::withTrashed()->where('user_id', $userId)->value('id');
        $result = null;
        if ($profileId) {
            // A client with several completed jobs contributes only once.
            $completedClients = Booking::withTrashed()
                ->where('provider_profile_id', $profileId)
                ->where('status', BookingStatus::Completed->value)
                ->select('client_id');
            $completedCount = (clone $completedClients)->distinct()->count('client_id');
            $reportingCount = Report::query()
                ->where('target_user_id', $userId)
                ->whereIn('reporter_id', $completedClients)
                ->distinct()->count('reporter_id');

            $result = [
                'eligible' => $reportingCount >= 10 && $completedCount > 0
                    && $reportingCount * 100 >= $completedCount * 85,
                'reporting_clients' => $reportingCount,
                'completed_clients' => $completedCount,
            ];
        }

        $cache[$userId] = $result;
        $request->attributes->set('provider_report_thresholds', $cache);

        return $result;
    }
}
