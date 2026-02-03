<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReligiousPlace;

class ReligiousPlacesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $places = [
            'Masjid al-Haram',
            'St. Mary’s Church',
            'ISKCON Temple',
            'Buddhist Temple',
            'Synagogue of London',
        ];

        foreach ($places as $place) {
            ReligiousPlace::create(['name' => $place]);
        }
    }
}
