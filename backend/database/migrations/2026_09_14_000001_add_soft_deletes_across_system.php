<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full CRUD with soft deletes across the system: every table an admin,
 * provider or client can now "delete" through the API keeps its row with a
 * `deleted_at` stamp so it can be reviewed and restored. Tables that are
 * pure history (audit_logs, booking_status_histories) are deliberately not
 * included - they must never be hideable.
 */
return new class extends Migration
{
    private const TABLES = [
        'barangays',
        'reviews',
        'messages',
        'reports',
        'chat_violations',
        'provider_payment_methods',
        'provider_availability_exceptions',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
