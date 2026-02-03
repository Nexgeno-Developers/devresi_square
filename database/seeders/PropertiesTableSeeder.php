<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the last reference number to generate the next one
        $lastProperty = DB::table('properties')->orderByDesc('prop_ref_no')->first();

        // If the table is empty, start with 'RESISQP0000001'
        $lastRefNo = $lastProperty ? $lastProperty->prop_ref_no : 'RESISQP0000000';

        // Insert 10 properties records
        for ($i = 1; $i <= 10; $i++) {
            // Extract the numerical part and increment it
            $newRefNo = 'RESISQP' . str_pad((int)substr($lastRefNo, -7) + $i, 7, '0', STR_PAD_LEFT);

            // Insert each property record
            DB::table('properties')->insert([
                'prop_ref_no' => $newRefNo,
                'prop_name' => 'property ' . $i,
                'line_1' => '12 Park Lane',
                'line_2' => 'Mayfair',
                'city' => 'London',
                'country' => 'United Kingdom',
                'postcode' => 'W1K 1QA',
                'property_type' => 'both',
                'transaction_type' => 'commercial',
                'specific_property_type' => 'house',
                'bedroom' => '5',
                'bathroom' => '3',
                'reception' => '6+',
                'parking' => '1',
                'parking_location' => 'b-124',
                'balcony' => '1',
                'garden' => '0',
                'service' => 'fully manged',
                'management' => 'premium management',
                'collecting_rent' => '1',
                'floor' => 'basement',
                'square_feet' => 450.0000,
                'square_meter' => 41.8064,
                'aspects' => 'north-east',
                'sales_current_status' => 'on hold',
                'letting_current_status' => 'let agreed',
                'sales_status_description' => 'Description',
                'letting_status_description' => 'Description',
                'available_from' => '2024-11-13',
                'pets_allow' => '1',
                'market_on' => json_encode(['resisquare', 'rightmove', 'zoopla', 'onthemarket']),
                'features' => json_encode(['Furnished', 'Unfurnished']),
                'furniture' => null,
                'kitchen' => json_encode(['Undercounter refrigerator without freezer', 'Gas oven', 'Washing machine']),
                'heating_cooling' => json_encode(['Air conditioning', 'Underfloor heating', 'Gas', 'Central heating']),
                'safety' => json_encode(['External CCTV Intruder alarm system', 'Smoke alarm', 'Carbon monoxide detector']),
                'other' => json_encode(['Roof Garden', 'Lift', 'Fireplace', 'Wood flooring']),
                'access_arrangement' => 'Access Arrangement1',
                'key_highlights' => 'Key Highlights1',
                'nearest_station' => '5,4',
                'nearest_school' => '4,6,5',
                'nearest_religious_places' => json_encode(['masjid' => 1, 'church' => 2, 'mandir' => 3]),
                'useful_information' => 'nothing2',
                'price' => 100.00,
                'ground_rent' => 400.00,
                'service_charge' => 20.00,
                'estate_charge' => 10.00,
                'miscellaneous_charge' => 12.00,
                'estate_charges_id' => null,
                'annual_council_tax' => 60.00,
                'council_tax_band' => 'A',
                'local_authority' => 'any',
                'letting_price' => 500.00,
                'tenure' => 'leasehold',
                'length_of_lease' => 12,
                'epc_rating' => 'A',
                'is_gas' => 1,
                'gas_safe_acknowledged' => 1,
                'photos' => 'https://www.youtube.com/watch?v=kG8RP6Rk0K8',
                'floor_plan' => null,
                'view_360' => null,
                'video_url' => 'https://www.youtube.com/watch?v=kG8RP6Rk0K8',
                'designation' => 'landlord',
                'branch' => 'branch2',
                'commission_percentage' => 4.00,
                'commission_amount' => 500.00,
                'step' => 9,
                'quick_step' => 4,
                'added_by' => 1,
                'deleted_by' => null,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
