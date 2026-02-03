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
        DB::table('tenancy_types')->insert([
            ['name' => 'AST'],
            ['name' => 'Common Law'],
            ['name' => 'Company'],
            ['name' => 'Short Let - AST'],
        ]);

        // Tenancytype::factory()->count(12)->create();
    }
}
