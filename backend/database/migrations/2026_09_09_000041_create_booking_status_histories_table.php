<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only lifecycle log: one row per status change, written by the
 * booking state machine inside the same transaction as the status update.
 *
 * from_status is null for the creation entry. Rows are never updated or
 * deleted by application code; the FK is RESTRICT so a booking with
 * history cannot be hard-deleted underneath the audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->restrictOnDelete();

            $table->enum('from_status', BookingStatus::values())->nullable();
            $table->enum('to_status', BookingStatus::values());
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
