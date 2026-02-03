<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RepairCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Insert parent categories first
        $parentCategories = [
            [
                'name' => 'Bathroom and Toilet',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'bath-orange.svg',
                'position' => 1
            ],
            [
                'name' => 'Kitchen',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'kitchen-orange.svg',
                'position' => 2
            ],
            [
                'name' => 'Heating and Boiler',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'heater-orange.svg',
                'position' => 3
            ],
            [
                'name' => 'Water and Leaks',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'pipe_leak-orange.svg',
                'position' => 4
            ],
            [
                'name' => 'Doors, Garages and Locks',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'garage_lock-orange.svg',
                'position' => 5
            ],
            [
                'name' => 'Internal floors, walls and ceilings',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'kitchen-orange.svg',
                'position' => 6
            ],
            [
                'name' => 'Lighting',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'lightning-orange.svg',
                'position' => 7
            ],
            [
                'name' => 'Window',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'curtains-orange.svg',
                'position' => 8
            ],
            [
                'name' => 'Exterior and Garden',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'tree-orange.svg',
                'position' => 9
            ],
            [
                'name' => 'Laundry',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 10
            ],
            [
                'name' => 'Furniture',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 11
            ],
            [
                'name' => 'Electricity',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 12
            ],
            [
                'name' => 'Hot Water',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 13
            ],
            [
                'name' => 'Alarms and Smoke Detectors',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 14
            ],
            [
                'name' => 'Pest / Vermin',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 15
            ],
            [
                'name' => 'Roof',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 16
            ],
            [
                'name' => 'Communal / Shared Facilities',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 17
            ],
            [
                'name' => 'Audiovisual',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 18
            ],
            [
                'name' => 'Internet',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 19
            ],
            [
                'name' => 'Utility Meter',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 20
            ],
            [
                'name' => 'Stairs',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 21
            ],
            [
                'name' => 'Smell Gas',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 22
            ],
            [
                'name' => 'Air Conditioning',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 23
            ],
            [
                'name' => 'Smell Oil',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 24
            ],
            [
                'name' => 'Fire',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 25
            ],
            [
                'name' => 'Property Inspection',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 26
            ],
            [
                'name' => 'Other',
                'parent_id' => null,
                'level' => 1,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 27
            ],
        ];

        // Insert parent categories into the repair_categories table
        DB::table('repair_categories')->insert($parentCategories);

        // Now insert child categories
        $childCategories = [
            // Additional level 2 categories for Bathroom and Toilet (parent_id = 1)
            [
                'name' => 'Basin',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 1
            ],
            [
                'name' => 'Bath',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 2
            ],
            [
                'name' => 'Shower',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 3
            ],
            [
                'name' => 'Electric Shower',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 4
            ],
            [
                'name' => 'Extractor Fan',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 5
            ],
            [
                'name' => 'WC/Toilet',
                'parent_id' => 1,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 6
            ],
            // Additional level 2 categories for Kitchen (parent_id = 2)
            [
                'name' => 'Appliances',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 1
            ],
            [
                'name' => 'Kitchen Units and Larder',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 2
            ],
            [
                'name' => 'Extractor Fan',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 3
            ],
            [
                'name' => 'Sink',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 4
            ],
            [
                'name' => 'Tap',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 5
            ],
            [
                'name' => 'Tiles',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 6
            ],
            [
                'name' => 'Worktop',
                'parent_id' => 2,
                'level' => 2,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 7
            ],
            // Level 3 categories for Bathroom and Toilet (parent_id = 1)
            [
                'name' => 'Basin on brackets',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 1
            ],
            [
                'name' => 'Basin on pedestal',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 2
            ],
            [
                'name' => 'Between basin and wall',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 3
            ],
            [
                'name' => 'Pipe leaking',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 4
            ],
            [
                'name' => 'Plug hole',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 5
            ],
            [
                'name' => 'Plug/Chain',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 6
            ],
            [
                'name' => 'Tap',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 7
            ],
            [
                'name' => 'Tiles',
                'parent_id' => 1,
                'level' => 3,
                'description' => '',
                'icon' => 'placeholder.svg',
                'position' => 8
            ]
        ];

        // Insert child categories into the repair_categories table
        DB::table('repair_categories')->insert($childCategories);
    }
}
