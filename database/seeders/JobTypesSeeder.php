<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define main job types and their sub-jobs
        $jobTypes = [
            'Check In' => [
                // 'Inventory Report',
                // 'Meter Readings',
                // 'Key Collection/Return',
                // 'Property Condition Check',
                // 'Tenant Handover',
                // 'Lock Change',
            ],
            'Check Out' => [
                // 'Inventory Report',
                // 'Meter Readings',
                // 'Key Collection/Return',
                // 'Property Condition Check',
                // 'Tenant Handover',
                // 'Lock Change',
            ],
            'Professional Clean' => [
                'Deep Cleaning',
                'Carpet Cleaning',
                'Window Cleaning',
                'Upholstery Cleaning',
                'Kitchen/Bathroom Sanitization',
                'End of Tenancy Cleaning',
            ],
            'Repair Work' => [
                'Plumbing',
                'Handyman Work',
                'Electrical Works',
            ],
            'Gas Safety Certification' => [
                'Boiler Inspection',
                'Gas Leak Testing',
                'Gas Appliance Servicing',
                'Flue & Ventilation Check',
                'Gas Pipework Check',
                'CO Detector Installation',
            ],
            'EICR' => [
                'Circuit Testing',
                'Fuse Box Inspection',
                'Wiring Condition Report',
                'PAT Testing',
                'Emergency Lighting Inspection',
            ],
            'EPC' => [
                'Thermal Imaging Inspection',
                'Loft Insulation Check',
                'Boiler Efficiency Testing',
                'Heating System Assessment',
                'Window/Door Insulation Check',
                'Renewable Energy Evaluation',
            ],
            'PAT' => [
                'Visual Inspection',
                'Earth Continuity Test',
                'Insulation Resistance Test',
                'Load & Functional Testing',
                'Labeling & Certification',
            ],
        ];

        // Insert main job types and sub-jobs
        foreach ($jobTypes as $mainType => $subJobs) {
            $mainTypeId = DB::table('job_types')->insertGetId([
                'name' => $mainType,
                'parent_id' => null, // Main type has no parent
                'level' => 1,
                'order_level' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($subJobs as $subJob) {
                DB::table('job_types')->insert([
                    'name' => $subJob,
                    'parent_id' => $mainTypeId, // Assign to its parent type
                    'level' => 2, // Sub-type level
                    'order_level' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
