<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores Laravel enums as strings. MySQL/TiDB require the
        // allowed values to be expanded before an operator can promote anyone.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'provider', 'admin', 'super_admin') NOT NULL DEFAULT 'client'");
        }
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'super_admin')->exists()) {
            throw new RuntimeException('Demote Super Admin accounts explicitly before rolling back this migration.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('client', 'provider', 'admin') NOT NULL DEFAULT 'client'");
        }
    }
};
