<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User-submitted complaints, polymorphic: about a user, a service, or a
 * booking (Q-5 resolved to "keep, minimal").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reportable_type', 255);
            $table->unsignedBigInteger('reportable_id');

            $table->enum('reason', ReportReason::values());
            $table->text('details')->nullable();

            $table->enum('status', ReportStatus::values())->default(ReportStatus::Open->value);
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('handling_notes')->nullable();

            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
