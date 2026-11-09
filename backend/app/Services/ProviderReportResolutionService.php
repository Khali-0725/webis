<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ProviderViolation;
use App\Models\Report;
use App\Models\User;

class ProviderReportResolutionService
{
    public const PENALTY_POINTS = 5;

    public function issueForResolvedReport(Report $report, User $admin): ?ProviderViolation
    {
        if (! $report->reporter?->isClient() || ! $report->target_user_id) {
            return null;
        }

        $profile = ProviderProfile::query()
            ->where('user_id', $report->target_user_id)
            ->lockForUpdate()
            ->first();
        if (! $profile) {
            return null;
        }

        $violation = ProviderViolation::firstOrCreate(
            ['report_id' => $report->id],
            [
                'provider_profile_id' => $profile->id,
                'issued_by' => $admin->id,
                'penalty_points' => self::PENALTY_POINTS,
                'reason' => $report->reason->label(),
            ],
        );

        if ($violation->wasRecentlyCreated) {
            $profile->forceFill([
                'performance_rate' => max(0, (int) $profile->performance_rate - self::PENALTY_POINTS),
                'violation_count' => (int) $profile->violation_count + 1,
            ])->save();
        }

        return $violation;
    }
}
