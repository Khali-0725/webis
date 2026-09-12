<?php

use App\Enums\ViolationAction;
use App\Enums\ViolationAdminStatus;
use App\Enums\ViolationCategory;
use App\Enums\ViolationSeverity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moderation record for every suspicious chat attempt.
 *
 * This is an audit table: user/conversation/message references are nullable
 * with nullOnDelete so the record - and the evidence in attempted_body -
 * survives even if the offending user or thread is later purged. Admin
 * access to flagged conversations is itself audit-logged (Phase 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_violations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();

            // Exactly what the sender tried to send, before any action.
            $table->text('attempted_body');

            $table->enum('category', ViolationCategory::values());
            $table->string('matched_rule', 100);
            $table->enum('severity', ViolationSeverity::values())->default(ViolationSeverity::Low->value);
            $table->enum('action_taken', ViolationAction::values());

            $table->enum('admin_status', ViolationAdminStatus::values())
                ->default(ViolationAdminStatus::Open->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Moderation queue and per-user history views.
            $table->index(['admin_status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_violations');
    }
};
