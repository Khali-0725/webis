<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The core transaction: a client books a service from a provider.
 *
 * Every important value is derived server-side (Phase 5): the client's
 * user id comes from the session, prices come from the service row, and
 * status changes go through the state machine. The client never posts
 * client_id, price, or status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Human-friendly reference shown in the UI (e.g. WB-8F3K2Q).
            // Immutable once generated; UNIQUE makes lookups by code safe.
            $table->string('booking_code', 20)->unique();

            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();

            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');

            $table->enum('status', BookingStatus::values())->default(BookingStatus::Pending->value);

            // quoted_price is set at creation from the service's pricing;
            // final_price is set when the provider completes the job.
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();

            $table->text('client_notes')->nullable();
            $table->text('provider_notes')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Conflict prevention (R-21) and the provider's schedule view.
            $table->index(['provider_profile_id', 'scheduled_date', 'status']);
            // "My bookings" for clients, per status tab.
            $table->index(['client_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
