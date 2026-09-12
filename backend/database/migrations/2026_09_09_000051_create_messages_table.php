<?php

use App\Enums\ModerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat messages inside a conversation.
 *
 * moderation_status records the filter's verdict for messages that were
 * SENT. Blocked attempts are never inserted here - they live only in
 * `chat_violations.attempted_body` so the platform never stores the
 * off-platform contact it just refused to relay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();

            $table->text('body');

            $table->enum('moderation_status', ModerationStatus::values())
                ->default(ModerationStatus::Allowed->value);

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Ordered pagination over a thread; the conversation FK also
            // covers cascade lookups.
            $table->index(['conversation_id', 'id']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
