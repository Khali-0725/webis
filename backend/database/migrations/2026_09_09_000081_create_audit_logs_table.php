<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for important actions (verification decisions, payment
 * reviews, suspensions, moderation access to flagged conversations).
 *
 * Append-only: the model exposes no update path, and only created_at is
 * maintained (UPDATED_AT = null). Polymorphic target so any model can be
 * audited without a pivot per entity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 100);

            $table->string('auditable_type', 255)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
