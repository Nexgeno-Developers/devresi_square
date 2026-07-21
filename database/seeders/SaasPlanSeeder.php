<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'landlord_basic',
                'name' => 'Landlord Basic',
                'target_account_type' => 'landlord',
                'description' => 'Starter plan for independent landlords.',
                'monthly_price_minor' => 2900,
                'annual_price_minor' => 29000,
                'currency' => 'GBP',
                'trial_days' => 7,
                'property_limit' => 3,
                'branch_limit' => 0,
                'staff_limit' => 0,
                'property_manager_limit' => 0,
                'allow_company_profile' => false,
                'allow_invoice_branding' => false,
                'allow_roles_permissions' => false,
                'allow_contact_login' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'estate_agent_company',
                'name' => 'Estate Agent Company',
                'target_account_type' => 'estate_agent_company',
                'description' => 'Company plan for estate agents with branches and staff.',
                'monthly_price_minor' => 14900,
                'annual_price_minor' => 149000,
                'currency' => 'GBP',
                'trial_days' => 7,
                'property_limit' => 50,
                'branch_limit' => 3,
                'staff_limit' => 10,
                'property_manager_limit' => 0,
                'allow_company_profile' => true,
                'allow_invoice_branding' => true,
                'allow_roles_permissions' => true,
                'allow_contact_login' => true,
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }

        $addons = [
            [
                'code' => 'extra_property',
                'name' => 'Extra Property',
                'addon_type' => 'property',
                'monthly_price_minor' => 500,
                'annual_price_minor' => 5000,
                'currency' => 'GBP',
                'grant_quantity' => 1,
                'is_stackable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'extra_branch',
                'name' => 'Extra Branch',
                'addon_type' => 'branch',
                'monthly_price_minor' => 2000,
                'annual_price_minor' => 20000,
                'currency' => 'GBP',
                'grant_quantity' => 1,
                'is_stackable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'extra_staff',
                'name' => 'Extra Staff',
                'addon_type' => 'staff',
                'monthly_price_minor' => 1000,
                'annual_price_minor' => 10000,
                'currency' => 'GBP',
                'grant_quantity' => 1,
                'is_stackable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'property_manager',
                'name' => 'Property Manager',
                'addon_type' => 'property_manager',
                'monthly_price_minor' => 1500,
                'annual_price_minor' => 15000,
                'currency' => 'GBP',
                'grant_quantity' => 1,
                'is_stackable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($addons as $addon) {
            Addon::updateOrCreate(
                ['code' => $addon['code']],
                $addon
            );
        }
    }
}
