<?php

use Database\Seeders\CaviteBarangaySeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new CaviteBarangaySeeder())->run();
    }

    public function down(): void
    {
        // Coordinates are reference data; keep them on rollback.
    }
};
