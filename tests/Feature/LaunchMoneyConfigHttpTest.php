<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Saas\ProductionMoneyConfig;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaunchMoneyConfigHttpTest extends TestCase
{
    public function test_assert_money_config_fails_when_production_mail_is_log(): void
    {
        config([
            'mail.default' => 'log',
            'mail.from.address' => 'hello@example.com',
            'services.stripe.secret' => 'sk_live_op',
            'services.stripe.webhook_secret' => 'whsec_op',
            'services.stripe.rent.secret' => 'sk_live_rent',
            'services.stripe.rent.webhook_secret' => 'whsec_rent',
        ]);

        $this->artisan('launch:assert-money-config', ['--env' => 'production'])
            ->assertFailed();
    }

    public function test_assert_money_config_fails_when_production_rent_secret_matches_operating(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'noreply@resisquare.test',
            'services.stripe.secret' => 'sk_live_same',
            'services.stripe.webhook_secret' => 'whsec_op',
            'services.stripe.rent.secret' => 'sk_live_same',
            'services.stripe.rent.webhook_secret' => 'whsec_rent',
        ]);

        $problems = app(ProductionMoneyConfig::class)->problems('production');

        $this->assertNotEmpty($problems);
        $this->assertTrue(
            collect($problems)->contains(fn (string $p) => str_contains($p, 'STRIPE_RENT_SECRET must not equal'))
        );
    }

    public function test_assert_money_config_passes_for_local_even_with_log_mailer(): void
    {
        config([
            'mail.default' => 'log',
            'mail.from.address' => 'hello@example.com',
        ]);

        $this->artisan('launch:assert-money-config', ['--env' => 'local'])
            ->assertSuccessful();

        $this->assertTrue(app(ProductionMoneyConfig::class)->isReady('local'));
    }

    public function test_suspended_account_is_redirected_to_billing_but_billing_stays_open(): void
    {
        [$user, $accountId] = $this->createLandlordAccount('suspended');

        $this->assertSame('suspended', \DB::table('accounts')->where('id', $accountId)->value('status'));
        $this->assertSame('suspended', \App\Models\Account::query()->findOrFail($accountId)->status);

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $response->assertRedirect(route('backend.billing.index'));

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.index'))
            ->assertOk();
    }

    public function test_past_due_account_retains_product_access(): void
    {
        [$user, $accountId] = $this->createLandlordAccount('past_due');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlordAccount(string $status): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Money Guard '.uniqid(),
            'email' => 'money-guard-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('plans')->insertGetId([
            'code' => 'money-guard-'.uniqid(),
            'name' => 'Money Guard',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 0,
            'annual_price_minor' => 0,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => 5,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => 1,
            'is_active' => 1,
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'status' => $status === 'past_due' ? 'past_due' : 'cancelled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }
}
