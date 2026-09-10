<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TenancySubStatus;

class TenancySubStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'Under Negotiation',
            'Offer Accepted',
            'Admin To Approve',
            'Accounts To Process',
            'Current Tenancy',
            'Current Tenancy (On Notice)',
            'Aborted',
            'Offer Rejected',
            'Offer Rejected – Refund Request',
            'Checked Out',
            'Checked Out – Deposit Dispute',
            'Checked Out – Deposit Settled',
            'Archive',
        ];

        foreach ($statuses as $status) {
            TenancySubStatus::firstOrCreate(['name' => $status]);
        }

        // TenancySubStatus::factory()->count(12)->create();
    }
}
