<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A rating + review pair - deliberately ONE table (MOD8): a rating cannot
 * exist without its booking, so a split ratings/reviews design would allow
 * an impossible-to-enforce 1:1.
 *
 * UNIQUE(booking_id) is the database-level guarantee that a completed
 * booking can be reviewed exactly once, by exactly the client who booked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->restrictOnDelete();

            // 1-5; bounds are enforced by validation (Phase 8) and the enum
            // style CHECK that MySQL does not offer for range checks here.
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            // Providers may hide abusive content; is_visible=false hides it
            // from public surfaces without destroying the record.
            $table->boolean('is_visible')->default(true);

            $table->text('provider_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'is_visible']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
