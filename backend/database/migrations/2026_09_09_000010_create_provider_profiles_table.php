<?php

use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provider-specific data, 1:1 with a provider-role user.
 *
 * Client-specific fields live directly on `users` (there are only a few);
 * providers carry ~15 extra columns plus cached aggregates, so they get
 * their own table. The rating/booking aggregates are recomputed inside the
 * transaction that closes a booking or stores a review - search sorting
 * cannot afford a live AVG() over reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('business_name', 150)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();

            $table->foreignId('base_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();

            // Provider's operating base; used for "nearest" ranking.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('verification_status', VerificationStatus::values())
                ->default(VerificationStatus::Pending->value);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Cached aggregates - maintained transactionally, never read raw.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('completed_bookings_count')->default(0);

            $table->boolean('is_accepting_bookings')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Verification queue and "can book right now" filters.
            $table->index(['verification_status', 'is_accepting_bookings'], 'pp_verif_accepting_idx');
            // Bounding-box prefilter for distance ranking.
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
