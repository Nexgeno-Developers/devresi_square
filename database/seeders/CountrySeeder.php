<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['code' => 'UK', 'name' => 'United Kingdom'],
            ['code' => 'IE', 'name' => 'Ireland'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
            ['code' => 'SA', 'name' => 'Saudi Arabia'],
            ['code' => 'QA', 'name' => 'Qatar'],
            ['code' => 'BH', 'name' => 'Bahrain'],
            ['code' => 'OM', 'name' => 'Oman'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'CA', 'name' => 'Canada'],
        ];

        foreach ($countries as $country) {
            Country::query()->firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
