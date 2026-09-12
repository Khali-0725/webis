<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links every account to the barangay they live in.
 *
 * Added as its own migration rather than editing the Phase 1 users
 * migration: barangays must exist before the foreign key can, and shipped
 * migrations are never rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('barangay_id')
                ->nullable()
                ->after('avatar_path')
                ->constrained()
                ->nullOnDelete();

            // Client-side barangay filtering in search and admin user lists.
            $table->index('barangay_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('barangay_id');
            $table->dropIndex(['barangay_id']);
        });
    }
};
