<?php

namespace Database\Factories;

use App\Models\TenancySubStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenancySubStatus>
 */
class TenancySubStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = TenancySubStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,  // Generating a fake name for the status
        ];
    }
}
