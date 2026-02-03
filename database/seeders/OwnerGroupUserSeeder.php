<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\OwnerGroupUser;
use Illuminate\Database\Seeder;

class OwnerGroupUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        OwnerGroupUser::create([
            'owner_group_id' => 1, // Ensure this ID exists in your owner_groups table
            'user_id' => 1,
            'is_main' => true,
        ]);

        OwnerGroupUser::create([
            'owner_group_id' => 1, // Again, ensure this ID exists
            'user_id' => 2,
            'is_main' => false,
        ]);
    }
}
