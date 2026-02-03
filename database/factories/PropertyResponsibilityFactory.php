<?php

namespace Database\Factories;

use App\Models\PropertyResponsibility;
use App\Models\User;
use App\Models\Branch;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyResponsibility>
 */
class PropertyResponsibilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = PropertyResponsibility::class;

    public function definition()
    {
        return [
            'property_id' => $this->faker->randomDigitNotNull(), // Random property ID
            'user_id' => User::factory(), // Creating a random User
            'branch_id' => Branch::factory(), // Creating a random Branch
            'designation_id' => Designation::factory(), // Creating a random Designation
            'commission_percentage' => $this->faker->randomFloat(2, 1, 10), // Random commission percentage between 1 and 10
            'commission_amount' => $this->faker->randomFloat(2, 100, 1000), // Random commission amount between 100 and 1000
        ];
    }
}
