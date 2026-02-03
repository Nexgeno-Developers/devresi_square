<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstateChargesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estate_charges')->insert([
            [
                'ref_no' => 101,
                'property_id' => 1,
                'ownergroup_id' => 1,
                'description' => 'Monthly service charge',
                'paid_by_landlord' => 1,
                'managed_by_property' => 0,
                'charge_landlord' => 1000,
                'tax' => 50,
                'schedule_charge' => 1200.00,
                'attachment' => 'document.pdf',
                'type' => 'Fixed',
                'due_date' => '2024-11-30',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'amount' => 1100,
                'frequency' => 'Monthly',
                'status' => 'Active',
                'added_by' => 1
            ]
        ]);
    }
}
