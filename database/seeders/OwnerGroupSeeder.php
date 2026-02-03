<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OwnerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Define sample data with property IDs
        $sampleData = [
            [
                'property_id' => 1, // Example property ID
                'purchased_date' => Carbon::now()->subYears(2)->toDateString(),
                'sold_date' => Carbon::now()->subYear(1)->toDateString(),
                'archived_date' => Carbon::now()->toDateString(),
                'status' => 'active',
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ],
            [
                'property_id' => 2, // Example property ID
                'purchased_date' => Carbon::now()->subMonths(6)->toDateString(),
                'sold_date' => null,
                'archived_date' => null,
                'status' => 'active',
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ],
            [
                'property_id' => 3, // Example property ID
                'purchased_date' => Carbon::now()->subYears(3)->toDateString(),
                'sold_date' => Carbon::now()->subYear(2)->toDateString(),
                'archived_date' => Carbon::now()->subMonth(1)->toDateString(),
                'status' => 'archived',
                'deleted_by' => 1, // Assuming a user with ID 1
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ]
        ];

        // Loop through the sample data
        foreach ($sampleData as $data) {
            // Check if the property_id exists in the properties table
            $propertyExists = DB::table('properties')->where('id', $data['property_id'])->exists();

            if ($propertyExists) {
                // Insert into the owner_group table only if the property_id exists
                DB::table('owner_group')->insert($data);
            } else {
                // Log or handle the case when the property_id doesn't exist
                Log::warning("Property ID {$data['property_id']} does not exist. Skipping insertion.");
            }
        }
    }
}
