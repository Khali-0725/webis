<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A 1:1 client↔provider thread.
 *
 * The UNIQUE(client_id, provider_user_id) pair means participants always
 * reuse the same thread - there is exactly one conversation per pair,
 * optionally attached to a booking. Unread counts are denormalised counters
 * maintained transactionally on send/read so the conversation list needs
 * no COUNT(*) per row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_user_id')->constrained('users')->restrictOnDelete();

            // Present when the thread was started from a booking context.
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('client_unread_count')->default(0);
            $table->unsignedInteger('provider_unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->unique(['client_id', 'provider_user_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
