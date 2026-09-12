<?php

use App\Enums\DocumentStatus;
use App\Enums\VerificationDocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents a provider uploads for verification (ID, clearances, ...).
 *
 * Files live on the private disk and are served only through the
 * policy-checked file controller, so `file_path` is never a public URL and
 * never leaves the API as a filesystem path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_verification_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('document_type', VerificationDocumentType::values());
            $table->string('file_path');
            $table->string('original_name', 255);
            $table->string('mime_type', 127);
            $table->unsignedInteger('size_bytes');

            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::Pending->value);
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_verification_documents');
    }
};
