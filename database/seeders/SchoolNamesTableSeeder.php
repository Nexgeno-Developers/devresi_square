<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolName;

class SchoolNamesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = [
            'Hampton High',
            'Tower House School',
            'Greenwich School',
            'St. Peter’s Academy',
            'East London Academy',
        ];

        foreach ($schools as $school) {
            SchoolName::create(['name' => $school]);
        }
    }
}
