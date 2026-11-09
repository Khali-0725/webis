<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('performance_rate')->default(100)->after('completed_bookings_count');
            $table->unsignedInteger('violation_count')->default(0)->after('performance_rate');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['performance_rate', 'violation_count']);
        });
    }
};
