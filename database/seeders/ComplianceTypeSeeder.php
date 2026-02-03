<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ComplianceType;

class ComplianceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $complianceTypes = [
            ['name' => 'Energy Performance Certificate (EPC)', 'description' => 'Indicates the energy efficiency of a property.', 'alias' => 'epc'],
            ['name' => 'Gas Safety Certificate', 'description' => 'Required annually for properties with gas appliances.', 'alias' => 'gas'],
            ['name' => 'Electrical Installation Condition Report (EICR)', 'description' => 'Ensures electrical safety every 5 years.', 'alias' => 'eicr'],
            ['name' => 'Landlord Registration', 'description' => 'Required in some areas for landlords to register.', 'alias' => 'landlord_registration'],


            // ['name' => 'Energy Performance Certificate (EPC)', 'description' => 'Indicates the energy efficiency of a property.'],
            // ['name' => 'Gas Safety Certificate', 'description' => 'Required annually for properties with gas appliances.'],
            // ['name' => 'Electrical Installation Condition Report (EICR)', 'description' => 'Ensures electrical safety every 5 years.'],
            // ['name' => 'Fire Risk Assessment', 'description' => 'Mandatory for shared common areas.'],
            // ['name' => 'Legionella Risk Assessment', 'description' => 'Ensures water systems are free from Legionella bacteria.'],
            // ['name' => 'Smoke and Carbon Monoxide Detector Compliance', 'description' => 'Ensures proper installation and functioning of smoke and CO alarms.'],
            // ['name' => 'Asbestos Management Report', 'description' => 'Identifies and manages asbestos risks in older properties.'],
            // ['name' => 'Portable Appliance Testing (PAT)', 'description' => 'Testing of electrical appliances provided by the landlord.'],
            // ['name' => 'HMO License', 'description' => 'License for properties rented to multiple tenants forming more than one household.'],
            // ['name' => 'Right to Rent Check', 'description' => 'Verifies tenants have the legal right to rent in the UK.'],
            // ['name' => 'Building Regulations Compliance', 'description' => 'Ensures structural changes comply with regulations.'],
            // ['name' => 'Planning Permission Compliance', 'description' => 'Ensures compliance with local planning permissions.'],
            // ['name' => 'Furniture and Furnishings (Fire Safety) Regulations', 'description' => 'Ensures furniture meets fire safety standards.'],
            // ['name' => 'Deposit Protection Scheme Compliance', 'description' => 'Ensures tenant deposits are protected.'],
            // ['name' => 'Rental Agreement Compliance', 'description' => 'Verifies tenancy agreements meet legal standards.'],
            // ['name' => 'Insurance Compliance', 'description' => 'Ensures landlords have adequate insurance coverage.'],
            // ['name' => 'Landlord Registration', 'description' => 'Required in some areas for landlords to register.'],
            // ['name' => 'Public Liability Insurance', 'description' => 'Covers liability claims for landlords.'],
            // ['name' => 'Property Licensing', 'description' => 'Mandatory licenses required by certain councils.'],
            // ['name' => 'Boiler Service Record', 'description' => 'Annual servicing of boilers for efficiency and safety.'],
            // ['name' => 'Emergency Lighting Compliance', 'description' => 'Required for communal areas in multi-occupancy buildings.'],
            // ['name' => 'Septic Tank Compliance', 'description' => 'Ensures septic tanks meet current regulations.'],
            // ['name' => 'Window and Glazing Safety', 'description' => 'Ensures compliance with safety standards for glass and windows.'],
            // ['name' => 'Waste Disposal and Recycling Compliance', 'description' => 'Ensures proper waste management.'],
        ];

        foreach ($complianceTypes as $type) {
            ComplianceType::create($type); // Use the model to insert records
        }
    }
}
