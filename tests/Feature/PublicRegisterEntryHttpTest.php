<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicRegisterEntryHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_without_a_plan_opens_the_entry_plan_form(): void
    {
        $planId = $this->entryPlanId();

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create Account', false)
            ->assertSee('name="plan_id" value="'.$planId.'"', false)
            ->assertSee(route('pricing'), false);
    }

    public function test_register_with_an_unknown_plan_returns_to_pricing(): void
    {
        $this->get(route('register', [
            'plan_id' => 99999999,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ]))->assertRedirect(route('pricing'));
    }

    public function test_pricing_trial_link_includes_the_entry_plan(): void
    {
        $planId = $this->entryPlanId();

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(route('register', [
                'plan_id' => $planId,
                'billing_cycle' => 'monthly',
                'account_type' => 'landlord',
            ]), false);
    }

    private function entryPlanId(): int
    {
        $planId = \DB::table('plans')
            ->where('is_active', 1)
            ->where('target_account_type', 'landlord')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');

        if ($planId) {
            return (int) $planId;
        }

        return (int) \DB::table('plans')->insertGetId([
            'code' => 'entry-'.uniqid(),
            'name' => 'Entry Landlord',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 2900,
            'annual_price_minor' => 29000,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => 5,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => 1,
            'is_active' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
