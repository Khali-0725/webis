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
        // Reference rows are retained because accounts and bookings may use them.
    }
};
