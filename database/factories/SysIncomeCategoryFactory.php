<?php

namespace Database\Factories;

use App\Models\SysIncomeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SysIncomeCategoryFactory extends Factory
{
    protected $model = SysIncomeCategory::class;

    public function definition(): array
    {
        $base = fake()->randomElement(['Rent', 'Service Charges', 'Late Fees', 'Other Income']);

        return [
            'name' => $base . ' ' . strtoupper(fake()->bothify('??##')),
            'is_active' => fake()->boolean(90),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
