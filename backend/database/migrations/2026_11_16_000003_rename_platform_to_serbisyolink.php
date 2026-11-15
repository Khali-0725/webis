<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('system_settings')
            ->where('key', 'platform.name')
            ->first();

        if ($setting && json_decode((string) $setting->value, true) === 'WEBIS') {
            DB::table('system_settings')
                ->where('id', $setting->id)
                ->update(['value' => json_encode('SERBISYOLINK'), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $setting = DB::table('system_settings')
            ->where('key', 'platform.name')
            ->first();

        if ($setting && json_decode((string) $setting->value, true) === 'SERBISYOLINK') {
            DB::table('system_settings')
                ->where('id', $setting->id)
                ->update(['value' => json_encode('WEBIS'), 'updated_at' => now()]);
        }
    }
};
