<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suspension_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('suspended_at');
            $table->text('explanation');
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_rationale')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'suspended_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspension_appeals');
    }
};
