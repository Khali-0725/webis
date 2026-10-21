<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_session_id')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_until')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_basis', 120)->nullable();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('terminated_by');
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn(['active_session_id', 'terminated_at', 'termination_reason', 'suspended_until', 'suspended_at', 'suspension_basis', 'suspension_reason']);
        });
    }
};
