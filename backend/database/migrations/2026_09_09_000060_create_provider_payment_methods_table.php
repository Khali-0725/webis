<?php

use App\Enums\PaymentMethodType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a provider gets paid: a QR Ph (or GCash/Maya/bank) channel with an
 * uploaded QR image and payment instructions.
 *
 * `account_ref_masked` is the only account identifier the client ever
 * sees (e.g. "**** 4567") - full credentials stay with the provider.
 * `qr_image_path` points at the private disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payment_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('type', PaymentMethodType::values());
            $table->string('account_name', 150);
            $table->string('account_ref_masked', 100)->nullable();
            $table->string('qr_image_path')->nullable();
            $table->text('instructions')->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['provider_profile_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payment_methods');
    }
};
