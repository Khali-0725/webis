<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->where('key', 'booking.min_lead_hours')
            ->update(['value' => json_encode(0), 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'booking.min_lead_hours')
            ->update(['value' => json_encode(24), 'updated_at' => now()]);
    }
};
