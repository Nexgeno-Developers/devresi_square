<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Designation;
use App\Models\Property;
use App\Models\PropertyResponsibility;

class PropertyResponsibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
           // Get some sample users, branches, and designations
           $users = User::all(); // Assuming you have users in the users table
           $branches = Branch::all(); // Get all branches from the branches table
           $designations = Designation::all(); // Get all designations from the designations table
           $properties = Property::all(); // Get all properties from the properties table

           // Ensure there are enough users, branches, and designations to avoid issues
           if ($users->isEmpty() || $branches->isEmpty() || $designations->isEmpty() || $properties->isEmpty()) {
               return; // Exit if any of the required tables are empty
           }

           // Create 10 PropertyResponsibility entries
           for ($i = 0; $i < 10; $i++) {
               PropertyResponsibility::create([
                   'property_id' => $properties->random()->id, // Randomly pick a property from the properties table
                   'user_id' => $users->random()->id, // Randomly pick a user from the users table
                   'branch_id' => $branches->random()->id, // Randomly pick a branch from the branches table
                   'designation_id' => $designations->random()->id, // Randomly pick a designation from the designations table
                   'commission_percentage' => rand(1, 100), // Random commission percentage for testing
                   'commission_amount' => rand(100, 1000), // Random commission amount for testing
                   'created_at' => now(),
                   'updated_at' => now(),
               ]);
           }

        // Using the factory to create 10 PropertyResponsibility records
        // PropertyResponsibility::factory()->count(1)->create();
    }
}
