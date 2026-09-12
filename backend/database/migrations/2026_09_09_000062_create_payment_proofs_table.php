<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of uploaded payment proofs for a payment.
 *
 * A rejected proof is superseded, not destroyed: re-submission appends a
 * new row and stamps the old one's superseded_at, preserving the audit
 * trail. Files live on the private disk; only MIME type, size and a
 * relative path are stored - never a filesystem-absolute path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_name', 255);
            $table->string('mime_type', 127);
            $table->unsignedInteger('size_bytes');

            $table->string('reference_number', 100)->nullable();

            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('superseded_at')->nullable();

            $table->timestamps();

            // "Current proof" scans and the payment's evidence history.
            $table->index(['payment_id', 'superseded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
