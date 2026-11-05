<?php

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('target_user_id')->nullable()->after('reporter_id');
        });

        // Earlier versions allowed several routes to the same person. Keep all
        // historical rows, including withdrawn ones, and claim each pair once.
        $claimed = [];
        // Prefer an active row when old duplicate reports include withdrawals.
        // The active row then remains visible in threshold counts.
        foreach ([false, true] as $withdrawn) {
            $query = DB::table('reports');
            $withdrawn ? $query->whereNotNull('deleted_at') : $query->whereNull('deleted_at');
            $query->orderBy('id')->chunkById(100, function ($reports) use (&$claimed): void {
                foreach ($reports as $report) {
                    if ($report->reporter_id === null) {
                        continue;
                    }

                    $targetUserId = $this->targetUserId($report);
                    if ($targetUserId === null) {
                        continue;
                    }

                    $key = $report->reporter_id.':'.$targetUserId;
                    if (isset($claimed[$key])) {
                        continue;
                    }

                    $claimed[$key] = true;
                    DB::table('reports')->where('id', $report->id)->update(['target_user_id' => $targetUserId]);
                }
            });
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->unique(['reporter_id', 'target_user_id'], 'reports_one_per_person_unique');
        });
    }

    private function targetUserId(object $report): ?int
    {
        if ($report->reportable_type === User::class) {
            return DB::table('users')->where('id', $report->reportable_id)->value('id');
        }

        if ($report->reportable_type === ProviderProfile::class) {
            return DB::table('provider_profiles')->where('id', $report->reportable_id)->value('user_id');
        }

        if ($report->reportable_type === Service::class) {
            $profileId = DB::table('services')->where('id', $report->reportable_id)->value('provider_profile_id');

            return $profileId ? DB::table('provider_profiles')->where('id', $profileId)->value('user_id') : null;
        }

        if ($report->reportable_type === Booking::class) {
            $booking = DB::table('bookings')->where('id', $report->reportable_id)->first(['client_id', 'provider_profile_id']);
            if (! $booking) {
                return null;
            }

            $providerId = DB::table('provider_profiles')->where('id', $booking->provider_profile_id)->value('user_id');

            return (int) $report->reporter_id === (int) $booking->client_id ? $providerId
                : ((int) $report->reporter_id === (int) $providerId ? $booking->client_id : null);
        }

        return null;
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_one_per_person_unique');
            $table->dropColumn('target_user_id');
        });
    }
};
