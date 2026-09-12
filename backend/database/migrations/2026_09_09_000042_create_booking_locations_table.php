<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The client's exact service location, in its own table - the physical
 * enforcement of the privacy rule (R-26).
 *
 * Because the coordinates are NOT columns on `bookings`, a Booking resource
 * cannot leak them by selecting the row: the relation must be deliberately
 * eager-loaded behind a policy check, and only the client, the assigned
 * provider (after acceptance), and administrators may do that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();

            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->string('address_line', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('landmark_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_locations');
    }
};
