<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventTypes = [
            'Viewing' => [
                'Buyer Viewing',
                'Tenant Viewing',
                'Virtual Viewing',
                'Second Viewing',
            ],
            'Valuation' => [
                'Sales Valuation',
                'Lettings Valuation',
                'Revaluation',
                'Virtual Valuation',
            ],
            'Call' => [
                'Buyer Registration Call',
                'Vendor Feedback Call',
                'Lettings Follow-up Call',
                'Mortgage Follow-up',
            ],
            'Meeting' => [
                'In-Branch Client Meeting',
                'Vendor Meeting',
                'Landlord Meeting',
                'Investor Meeting',
            ],
            'Follow-Up' => [
                'Post-Viewing Follow-Up',
                'Lead Nurture Follow-Up',
                'Inactive Landlord Follow-Up',
            ],
            'Inspection' => [
                'Property Inspection',
                'Mid-Term Inspection',
                'Check-Out Inspection',
            ],
            'Contract' => [
                'Sales Agreement Signing',
                'Tenancy Agreement Signing',
                'Renewal Discussion',
            ],
            'Maintenance' => [
                'Maintenance Request Logged',
                'Contractor Visit',
                'Repair Completion',
            ],
            'Move-In/Move-Out' => [
                'Move-In Scheduled',
                'Key Collection',
                'Move-Out Inspection',
            ],
            'Reminder' => [
                'Gas Safety Renewal',
                'License Expiry',
                'Rent Review Due',
            ],
            'Offer' => [
                'Offer Received',
                'Offer Accepted',
                'Offer Withdrawn',
            ],
            'Tenancy Check' => [
                'Right to Rent Check',
                'Tenancy Renewal Check',
                'Deposit Scheme Check',
            ],
            'Mortgage Appointment' => [
                'Mortgage Advisor Meeting',
                'Mortgage Application Follow-Up',
            ],
        ];

        foreach ($eventTypes as $typeName => $subTypes) {
            $eventTypeId = DB::table('event_types')->insertGetId([
                'name' => $typeName,
                'slug' => Str::slug($typeName),
                'description' => $typeName . ' related events',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($subTypes as $subTypeName) {
                DB::table('event_sub_types')->insert([
                    'event_type_id' => $eventTypeId,
                    'name' => $subTypeName,
                    'slug' => Str::slug($subTypeName),
                    'description' => $subTypeName . ' event',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
