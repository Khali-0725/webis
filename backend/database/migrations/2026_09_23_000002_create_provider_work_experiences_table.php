<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Past work history entries a provider lists on their profile, separate from
 * their current published services and the single `experience_years` count -
 * e.g. "Electrician at ABC Corp, 2018-2021". `ended_on` null means ongoing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_work_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->string('role_title', 150);
            $table->string('employer_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->date('started_on');
            $table->date('ended_on')->nullable();

            $table->timestamps();

            $table->index('provider_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_work_experiences');
    }
};
