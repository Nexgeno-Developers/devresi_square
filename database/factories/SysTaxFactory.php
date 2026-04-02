<?php

namespace Database\Factories;

use App\Models\SysTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class SysTaxFactory extends Factory
{
    protected $model = SysTax::class;

    public function definition(): array
    {
        $base = fake()->randomElement(['VAT', 'GST', 'Sales Tax', 'Service Tax']);

        return [
            'name' => $base . ' ' . strtoupper(fake()->bothify('??##')),
            'rate' => fake()->randomFloat(2, 0, 25),
            'is_active' => fake()->boolean(85),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
