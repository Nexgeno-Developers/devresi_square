<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /*public function run(): void
    {
        $users = [
            ['name' => 'tanveer', 'email' => 'tanveer@example.com', 'role_id' => 1, 'password' => bcrypt('password')],
            ['name' => 'rai', 'email' => 'rai@example.com', 'role_id' => 2, 'password' => bcrypt('password')],
            ['name' => 'jatinder', 'email' => 'jatinder@example.com', 'role_id' => 3, 'password' => bcrypt('password')],
            ['name' => 'umair', 'email' => 'umair@example.com', 'role_id' => 4, 'password' => bcrypt('password')],
            ['name' => 'rashid', 'email' => 'rashid@example.com', 'role_id' => 5, 'password' => bcrypt('password')],
            ['name' => 'faisal', 'email' => 'faisal@example.com', 'role_id' => 6, 'password' => bcrypt('password')],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }*/
        public function run(): void
    {
        $domain = 'resisqaure.co.uk';

        $users = [
            [
                'name'  => 'Rai',
                'email' => "rai@{$domain}",
                'role'  => 'Landlord',
                // 'category_id' => 1,
                'user_type' => 'landlord',
                'first_name' => 'Rai',
                'middle_name' => '',
                'last_name' => '',
                'phone' => '1234567890',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'postcode' => '12345',
                'city' => 'New York',
                'country' => '',
                'status' => 1,
                'updated_by' => null,
            ],
            [
                'name'  => 'Tanveer',
                'email' => "tanveer@{$domain}",
                'role'  => 'Super Admin',
                // 'category_id' => 2,
                'user_type' => 'super_admin',
                'first_name' => 'Tanveer',
                'middle_name' => '',
                'last_name' => '',
                'phone' => '1234567890',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'postcode' => '12345',
                'city' => 'New York',
                'country' => '',
                'status' => 1,
                'updated_by' => null,
            ],
            [
                'name'  => 'Jatinder',
                'email' => "Jatinder@{$domain}",
                'role'  => 'Property Manager',
                // 'category_id' => 3,
                'user_type' => 'property_manager',
                'first_name' => 'Jatinder',
                'middle_name' => '',
                'last_name' => '',
                'phone' => '1234567890',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'postcode' => '12345',
                'city' => 'New York',
                'country' => '',
                'status' => 1,
                'updated_by' => null,
            ],
            [
                'name'  => 'umair',
                'email' => "umair@{$domain}",
                'role'  => 'Staff',
                // 'category_id' => 4,
                'user_type' => 'staff',
                'first_name' => 'Umair',
                'middle_name' => '',
                'last_name' => '',
                'phone' => '1234567890',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'postcode' => '12345',
                'city' => 'New York',
                'country' => '',
                'status' => 1,
                'updated_by' => null,
            ],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'              => $u['name'],
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'), // change to secure default
                    'remember_token'    => Str::random(10),
                    
                    // New columns
                    // 'category_id'       => $u['category_id'],
                    'first_name'        => $u['first_name'],
                    'middle_name'       => $u['middle_name'],
                    'last_name'         => $u['last_name'],
                    'phone'             => $u['phone'],
                    'address_line_1'    => $u['address_line_1'],
                    'address_line_2'    => $u['address_line_2'],
                    'postcode'          => $u['postcode'],
                    'city'              => $u['city'],
                    'country'           => $u['country'],
                    'status'            => $u['status'],
                    'updated_by'        => $u['updated_by'],
                ]
            );

            // Assign the role (will attach if not already)
            $user->assignRole($u['role']);
        }
    }
}
