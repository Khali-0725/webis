<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: foreign keys require parent tables to be seeded first.
     * Reference data must exist before the demo provider profile is created.
     * DemoDataSeeder then creates the realistic demo dataset.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo accounts and synthetic verification documents must not be seeded in production.');
        }

        $this->call([
            // Phase 1: reference data
            BarangaySeeder::class,
            ServiceCategorySeeder::class,

            // Phase 2: demo accounts and providers
            DemoAccountSeeder::class,

            // Phase 3: clients, services, bookings, reviews, etc.
            DemoDataSeeder::class,
        ]);
    }
}
