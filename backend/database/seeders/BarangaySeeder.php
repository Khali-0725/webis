<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

/**
 * Seeds the 41 barangays of Tanza, Cavite with approximate centroid
 * coordinates derived from public PSGC and OpenStreetMap data.
 *
 * Coordinates are marked approximate — they are not surveyed data.
 */
class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            ['name' => 'Amaya I',            'latitude' => 14.3410, 'longitude' => 120.8550],
            ['name' => 'Amaya II',           'latitude' => 14.3380, 'longitude' => 120.8510],
            ['name' => 'Amaya III',          'latitude' => 14.3355, 'longitude' => 120.8470],
            ['name' => 'Amaya IV',           'latitude' => 14.3330, 'longitude' => 120.8430],
            ['name' => 'Amaya V',            'latitude' => 14.3300, 'longitude' => 120.8400],
            ['name' => 'Amaya VI',           'latitude' => 14.3270, 'longitude' => 120.8370],
            ['name' => 'Amaya VII',          'latitude' => 14.3245, 'longitude' => 120.8340],
            ['name' => 'Bagtas',             'latitude' => 14.3480, 'longitude' => 120.8620],
            ['name' => 'Biga',               'latitude' => 14.3560, 'longitude' => 120.8700],
            ['name' => 'Biwas',              'latitude' => 14.3200, 'longitude' => 120.8510],
            ['name' => 'Bukal',              'latitude' => 14.3600, 'longitude' => 120.8780],
            ['name' => 'Calibuyo',           'latitude' => 14.3120, 'longitude' => 120.8400],
            ['name' => 'Capipisa',           'latitude' => 14.3050, 'longitude' => 120.8350],
            ['name' => 'Daang Amaya I',      'latitude' => 14.3440, 'longitude' => 120.8580],
            ['name' => 'Daang Amaya II',     'latitude' => 14.3420, 'longitude' => 120.8540],
            ['name' => 'Daang Amaya III',    'latitude' => 14.3400, 'longitude' => 120.8600],
            ['name' => 'Halayhay',           'latitude' => 14.3520, 'longitude' => 120.8660],
            ['name' => 'Julugan I',          'latitude' => 14.3150, 'longitude' => 120.8460],
            ['name' => 'Julugan II',         'latitude' => 14.3130, 'longitude' => 120.8440],
            ['name' => 'Julugan III',        'latitude' => 14.3100, 'longitude' => 120.8420],
            ['name' => 'Julugan IV',         'latitude' => 14.3080, 'longitude' => 120.8390],
            ['name' => 'Julugan V',          'latitude' => 14.3060, 'longitude' => 120.8360],
            ['name' => 'Julugan VI',         'latitude' => 14.3040, 'longitude' => 120.8330],
            ['name' => 'Julugan VII',        'latitude' => 14.3020, 'longitude' => 120.8300],
            ['name' => 'Julugan VIII',       'latitude' => 14.3000, 'longitude' => 120.8280],
            ['name' => 'Lambingan',          'latitude' => 14.3500, 'longitude' => 120.8640],
            ['name' => 'Mulawin',            'latitude' => 14.3260, 'longitude' => 120.8530],
            ['name' => 'Paradahan I',        'latitude' => 14.3180, 'longitude' => 120.8490],
            ['name' => 'Paradahan II',       'latitude' => 14.3160, 'longitude' => 120.8470],
            ['name' => 'Poblacion I',        'latitude' => 14.3460, 'longitude' => 120.8610],
            ['name' => 'Poblacion II',       'latitude' => 14.3450, 'longitude' => 120.8600],
            ['name' => 'Poblacion III',      'latitude' => 14.3452, 'longitude' => 120.8595],
            ['name' => 'Punta I',            'latitude' => 14.2980, 'longitude' => 120.8250],
            ['name' => 'Punta II',           'latitude' => 14.2960, 'longitude' => 120.8230],
            ['name' => 'Sahud-Ulan',         'latitude' => 14.3540, 'longitude' => 120.8680],
            ['name' => 'Sanja Mayor',        'latitude' => 14.3580, 'longitude' => 120.8720],
            ['name' => 'Santol',             'latitude' => 14.3620, 'longitude' => 120.8760],
            ['name' => 'Tanauan',            'latitude' => 14.3140, 'longitude' => 120.8450],
            ['name' => 'Tres Cruses',        'latitude' => 14.3220, 'longitude' => 120.8520],
            ['name' => 'Trinidad',           'latitude' => 14.3240, 'longitude' => 120.8540],
            ['name' => 'Wawa I',             'latitude' => 14.3640, 'longitude' => 120.8800],
        ];

        foreach ($barangays as $data) {
            Barangay::firstOrCreate(
                ['name' => $data['name'], 'municipality' => 'Tanza'],
                array_merge($data, [
                    'municipality' => 'Tanza',
                    'province' => 'Cavite',
                    'is_active' => true,
                ])
            );
        }

        $this->command?->info('Seeded '.count($barangays).' Tanza barangays (coordinates are approximate).');
    }
}
