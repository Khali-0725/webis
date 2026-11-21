<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('barangays')
            ->where('municipality', 'Naic')
            ->where('name', 'Sabang')
            ->update([
                'latitude' => 14.3146,
                'longitude' => 120.7930,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('barangays')
            ->where('municipality', 'Naic')
            ->where('name', 'Sabang')
            ->update([
                'latitude' => 14.32,
                'longitude' => 120.77,
                'updated_at' => now(),
            ]);
    }
};
