<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Designation;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Designation::create(['title' => 'Manager']);
        Designation::create(['title' => 'Negotiator']);
        Designation::create(['title' => 'Lister']);
        Designation::create(['title' => 'Assistant']);
    }
}
