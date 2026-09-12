<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalised skill tags per provider ("aircon cleaning", "welding", ...).
 * One row per skill; the UNIQUE pair makes re-submission idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->string('skill', 100);

            $table->timestamps();

            $table->unique(['provider_profile_id', 'skill']);
            $table->index('skill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_skills');
    }
};
