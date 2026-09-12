<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring weekly availability, e.g. "Mon-Fri 08:00-17:00, 60-minute
 * slots". One row per weekday per provider. Free-slot computation
 * (Phase 5) subtracts existing bookings from these windows and from any
 * exception for the specific date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_availability_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            // 0 = Sunday ... 6 = Saturday (matches PHP's `w` and Carbon's
            // dayOfWeek).
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(60);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['provider_profile_id', 'day_of_week', 'is_active'], 'par_provider_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_availability_rules');
    }
};
