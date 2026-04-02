<?php

namespace Database\Factories;

use App\Models\SysSaleInvoice;
use App\Models\SysSaleInvoiceItem;
use App\Models\SysTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class SysSaleInvoiceItemFactory extends Factory
{
    protected $model = SysSaleInvoiceItem::class;

    public function definition(): array
    {
        return [
            'sale_invoice_id' => SysSaleInvoice::factory(),
            'item_name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'quantity' => fake()->randomFloat(2, 1, 20),
            'rate' => fake()->randomFloat(2, 5, 800),
            'discount' => fake()->randomFloat(2, 0, 100),
            'tax_id' => fake()->boolean(80) ? SysTax::factory() : null,
            'tax_rate' => fake()->optional(0.8)->randomFloat(2, 0, 25),
            'tax_amount' => fake()->optional(0.8)->randomFloat(2, 0, 500),
            'line_total' => fake()->randomFloat(2, 10, 5000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
