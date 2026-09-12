<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds 8 service categories commonly found in Tanza, Cavite.
 * Aligned with the thesis's nine functional modules.
 */
class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plumbing',                'icon' => 'wrench',       'description' => 'Pipe repair, installation, fixture replacement and leak detection.',       'min_price' => 300,  'max_price' => 5000],
            ['name' => 'Electrical',              'icon' => 'zap',          'description' => 'Wiring, outlet installation, circuit-breaker replacement and lighting.',    'min_price' => 350,  'max_price' => 8000],
            ['name' => 'Aircon Services',          'icon' => 'thermometer',  'description' => 'Cleaning, freon recharge, installation and repair of AC units.',           'min_price' => 400,  'max_price' => 6000],
            ['name' => 'Carpentry',               'icon' => 'hammer',       'description' => 'Furniture repair, cabinet making, door/window fitting.',                   'min_price' => 500,  'max_price' => 15000],
            ['name' => 'House Cleaning',          'icon' => 'sparkles',     'description' => 'Deep cleaning, regular housekeeping and move-in/move-out cleaning.',        'min_price' => 500,  'max_price' => 5000],
            ['name' => 'Painting',                'icon' => 'paintbrush',   'description' => 'Interior and exterior painting, wall preparation and finishing.',           'min_price' => 1000, 'max_price' => 25000],
            ['name' => 'Appliance Repair',        'icon' => 'settings',     'description' => 'Repair and troubleshooting of household appliances and electronics.',       'min_price' => 300,  'max_price' => 5000],
            ['name' => 'General Home Maintenance', 'icon' => 'home',         'description' => 'Minor repairs, fixture installation and general handyman tasks.',          'min_price' => 250,  'max_price' => 8000],
        ];

        foreach ($categories as $i => $data) {
            ServiceCategory::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                array_merge($data, [
                    'slug' => Str::slug($data['name']),
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                ])
            );
        }

        $this->command?->info('Seeded '.count($categories).' service categories.');
    }
}
