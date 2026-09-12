<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed service taxonomy (Plumbing, Electrical, ...).
 *
 * Categories are soft-deleted: services and analytics reference them, and
 * historical bookings must keep resolving their category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 255)->nullable();
            $table->string('icon', 50)->nullable();

            // Optional price guidance shown on the browse page (Q-4:
            // informational only - real prices come from services).
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
