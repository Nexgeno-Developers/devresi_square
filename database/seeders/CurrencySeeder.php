<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Currency::create([
            'name' => 'British Pound',
            'code' => 'GBP',
            'symbol' => '£',
            'symbol_position' => 'before',
            'exchange_rate' => 1.000000, // GBP is the base currency
            'is_default' => true, // Set as the default currency
            'active' => true,
        ]);

        // Add other currencies if needed
    }
}
