<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstateChargesItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('estate_charges_items')->insert([
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'estate_charge_id' => 1,
                'amount' => 1100.00,
                'tax' => 50.00,
                'tax_amount' => 50.00,
                'charge_attachment' => 'invoice_january.pdf',
                'status' => 'Paid'
            ]
        ]);
    }
}
