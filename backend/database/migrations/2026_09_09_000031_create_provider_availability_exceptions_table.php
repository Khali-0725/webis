<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-off overrides of the weekly pattern: a fully closed day
 * (is_closed = true, no times) or a special partial window
 * (is_closed = false with explicit start/end).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_availability_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason', 255)->nullable();

            $table->timestamps();

            $table->unique(['provider_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_availability_exceptions');
    }
};
