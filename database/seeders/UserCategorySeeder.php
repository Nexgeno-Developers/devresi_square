<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserCategory;

class UserCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        UserCategory::create(['name' => 'Owner', 'status' => 1]);
        UserCategory::create(['name' => 'Property Manager', 'status' => 1]);
        UserCategory::create(['name' => 'Tenant', 'status' => 1]);
        UserCategory::create(['name' => 'Landlord', 'status' => 1]);
        UserCategory::create(['name' => 'Agent', 'status' => 1]);
        UserCategory::create(['name' => 'Contractor', 'status' => 1]);
        UserCategory::create(['name' => 'Maintenance', 'status' => 1]);
        UserCategory::create(['name' => 'Service Provider', 'status' => 1]);
    }
}
