<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: foreign keys require parent tables to be seeded first.
     * DemoAccountSeeder creates the 4 labelled demo accounts (admin, client,
     * provider, suspended). BarangaySeeder and ServiceCategorySeeder populate
     * reference data. DemoDataSeeder creates the realistic demo dataset.
     */
    public function run(): void
    {
        $this->call([
            // Phase 1: demo accounts
            DemoAccountSeeder::class,

            // Phase 2: reference data (must come before DemoDataSeeder)
            BarangaySeeder::class,
            ServiceCategorySeeder::class,

            // Phase 2: providers, clients, services, bookings, reviews, etc.
            DemoDataSeeder::class,
        ]);
    }
}
