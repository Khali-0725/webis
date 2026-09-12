<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barangays a provider is willing to serve - the "service area".
 *
 * A plain pivot: no timestamps, no extra columns. The UNIQUE pair makes
 * re-saving the area set idempotent (Phase 4 uses a sync-style PUT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_service_areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();

            $table->unique(['provider_profile_id', 'barangay_id']);
            // "Which providers serve this barangay?" scans.
            $table->index('barangay_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_service_areas');
    }
};
