<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master list of barangays. The thesis scopes discovery to Tanza, Cavite
 * (41 barangays), so the table is seeded from the official PSGC list and
 * carries approximate centroid coordinates for distance ranking.
 *
 * Barangays are deactivated, never deleted: users, provider profiles,
 * service areas and booking locations all reference them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('municipality', 120)->default('Tanza');
            $table->string('province', 120)->default('Cavite');

            // Approximate centroids in degrees. DECIMAL(10,7) gives ~1 cm
            // resolution; no GPS drift is possible from float rounding.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // A barangay name is only unique within its municipality.
            $table->unique(['name', 'municipality']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
