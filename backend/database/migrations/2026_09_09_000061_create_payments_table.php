<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One payment per booking (UNIQUE booking_id).
 *
 * Amounts come from the booking, never from the request. VERIFIED means a
 * human reviewed the uploaded proof - nothing in this system pretends a
 * bank transferred money. Rejected payments keep their record; the client
 * re-submits proof, which appends to `payment_proofs`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_payment_method_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('PHP');

            $table->string('reference_number', 100)->nullable();
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::Pending->value);

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['provider_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
