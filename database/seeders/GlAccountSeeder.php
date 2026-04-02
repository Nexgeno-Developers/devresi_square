<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GlAccount;

class GlAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ================= ASSETS =================
            ['code' => '1000', 'name' => 'Cash in Hand', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'Bank Account', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable (Tenants)', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Security Deposit Receivable', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Advance to Contractors', 'type' => 'asset'],

            // ================= LIABILITIES =================
            ['code' => '2000', 'name' => 'Accounts Payable (Contractors)', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Owner Payable', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'Tenant Security Deposit Payable', 'type' => 'liability'],
            ['code' => '2300', 'name' => 'Advance Rent Received', 'type' => 'liability'],

            // ================= INCOME =================
            ['code' => '4000', 'name' => 'Rental Income', 'type' => 'income'],
            ['code' => '4100', 'name' => 'Commission Income', 'type' => 'income'],
            ['code' => '4200', 'name' => 'Late Fee Income', 'type' => 'income'],
            ['code' => '4300', 'name' => 'Other Property Income', 'type' => 'income'],

            // ================= EXPENSE =================
            ['code' => '5000', 'name' => 'Maintenance Expense', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Contractor Expense', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Utilities Expense', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Management Expense', 'type' => 'expense'],

            // ================= EQUITY =================
            ['code' => '3000', 'name' => 'Owner Capital', 'type' => 'equity'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity'],
        ];

        foreach ($accounts as $account) {
            GlAccount::updateOrCreate(['code' => $account['code']], $account + ['is_active' => true]);
        }
    }
}

