<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('branches')->insert([
            [
                'name' => 'London HQ',
                'address' => '123 Baker Street',
                'city' => 'London',
                'postcode' => 'W1U 6AG',
                'country' => 'UK',
                'user_email' => 'london@example.com',
                'user_phone' => '+44 20 7946 0958',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manchester Branch',
                'address' => '456 Deansgate',
                'city' => 'Manchester',
                'postcode' => 'M3 4LY',
                'country' => 'UK',
                'user_email' => 'manchester@example.com',
                'user_phone' => '+44 161 850 0978',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
