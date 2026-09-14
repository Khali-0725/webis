<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return $user->id === $report->reporter_id || $user->isAdmin();
    }

    /**
     * The reporter may edit or withdraw their report only while it is still
     * Open - once an admin has picked it up (Reviewing) or closed it, the
     * record is part of the moderation trail.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->id === $report->reporter_id && $report->status === ReportStatus::Open;
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->update($user, $report);
    }
}
