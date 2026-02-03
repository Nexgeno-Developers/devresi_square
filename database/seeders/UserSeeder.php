<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
    }
}
