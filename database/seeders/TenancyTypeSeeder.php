<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TenancyType;

class TenancyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'APT',
            'Common Law',
            'Company',
            'Short Let - AST',
            'Assured Shorthold Tenancy',
        ] as $name) {
            TenancyType::firstOrCreate(['name' => $name]);
        }

        // Tenancytype::factory()->count(12)->create();
    }
}
