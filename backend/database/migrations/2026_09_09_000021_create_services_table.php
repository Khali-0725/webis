<?php

use App\Enums\PricingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A service listing offered by a provider.
 *
 * Business rule enforced at the service layer (Phase 4): only providers
 * with verification_status = approved may set published_at. The schema
 * stays permissive; authorization never lives in the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();

            $table->string('title', 150);
            $table->text('description');

            $table->enum('pricing_type', PricingType::values())->default(PricingType::Fixed->value);
            // For fixed/hourly: the price (or hourly rate). For quote-based:
            // null, with min/max as the provider's suggested range.
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();

            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Category browse + active-only scans.
            $table->index(['service_category_id', 'is_active']);
            $table->index(['provider_profile_id', 'is_active']);
            // "Newest" sort.
            $table->index('created_at');

            if (DB::getDriverName() === 'mysql') {
                // Keyword search runs on MySQL's native FULLTEXT (production).
                // SQLite (the test database) has no FULLTEXT; Phase 4 falls
                // back to LIKE there, so the index is MySQL-only.
                $table->fullText(['title', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
