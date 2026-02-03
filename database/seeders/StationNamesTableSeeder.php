<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StationName;


class StationNamesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            'Bakerloo',
            'Jubilee',
            'Northern',
            'Central',
            'Victoria',
        ];

        foreach ($stations as $station) {
            StationName::create(['name' => $station]);
        }
    }
}
