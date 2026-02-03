<?php

namespace Database\Factories;

use App\Models\TenancyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenancyType>
 */
class TenancyTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = TenancyType::class;


    public function definition(): array
    {
        return [
            'name' => $this->faker->word,  // Generating a fake name for the status
        ];
    }
}
