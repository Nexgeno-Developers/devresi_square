<?php

namespace Database\Factories;

use App\Models\SysExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SysExpenseCategoryFactory extends Factory
{
    protected $model = SysExpenseCategory::class;

    public function definition(): array
    {
        $base = fake()->randomElement(['Maintenance', 'Utilities', 'Repairs', 'Admin']);

        return [
            'name' => $base . ' ' . strtoupper(fake()->bothify('??##')),
            'is_active' => fake()->boolean(90),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
