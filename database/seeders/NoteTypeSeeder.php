<?php

namespace Database\Seeders;

use App\Models\NoteType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NoteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NoteType::insert([
            ['name' => 'Email'],
            ['name' => 'Call'],
            ['name' => 'Text'],
            ['name' => 'General'],
            ['name' => 'MIS'],
        ]);
    }
}
