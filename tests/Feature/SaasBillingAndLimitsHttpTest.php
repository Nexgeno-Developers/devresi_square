<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Property;
use App\Models\User;
use App\Services\Saas\AccountLimitService;
use App\Services\Saas\PortalAccessService;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaasBillingAndLimitsHttpTest extends TestCase
{
    public function test_landlord_owner_can_open_billing_and_staff_or_admin_cannot(): void
    {
        [$owner, $accountId] = $this->createLandlord('owner');
        [$staff] = $this->createMembershipOnAccount($accountId, 'Staff', 'staff');
        [$admin] = $this->createMembershipOnAccount($accountId, 'Landlord', 'admin');

        $this->actingAs($owner)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.index'))
            ->assertOk();

        $this->actingAs($staff)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.index'))
            ->assertForbidden();
    }

    public function test_landlord_cannot_open_saas_catalogue(): void
    {
        [$user, $accountId] = $this->createLandlord('owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.saas.plans.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.saas.accounts.index'))
            ->assertForbidden();
    }

    public function test_checkout_success_url_does_not_activate_the_account(): void
    {
        [$user, $accountId] = $this->createLandlord('owner');

        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'past_due']);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.success', ['checkout_ref' => 'cs_test_should_not_activate']))
            ->assertOk()
            ->assertSee('Payment processing', false);

        $this->assertSame('past_due', \DB::table('accounts')->where('id', $accountId)->value('status'));
    }

    public function test_webhook_replay_is_ignored(): void
    {
        $eventId = 'evt_replay_'.uniqid();
        $event = (object) [
            'id' => $eventId,
            'type' => 'checkout.session.expired',
            'data' => (object) [
                'object' => (object) [
                    'metadata' => [
                        'type' => 'other',
                    ],
                ],
            ],
        ];

        $service = app(StripeWebhookService::class);

        $service->handle($event);
        $this->assertTrue(Cache::has('stripe_webhook_event:'.$eventId));

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($eventId) {
                return $message === 'Stripe webhook ignored: duplicate event'
                    && ($context['event_id'] ?? null) === $eventId;
            });

        $service->handle($event);
    }

    public function test_portal_invite_is_blocked_when_portal_user_limit_is_full(): void
    {
        [$landlord, $accountId] = $this->createLandlord('owner', 1);
        $account = Account::query()->findOrFail($accountId);

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '1 Portal Limit Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        [$tenant] = $this->createMembershipOnAccount($accountId, 'Tenant', 'tenant');

        $this->assertFalse(app(AccountLimitService::class)->canAddPortalUser($account));

        $extra = User::create([
            'name' => 'Extra Tenant',
            'email' => 'portal-limit-extra-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(PortalAccessService::class)->grantPropertyAccess(
            $account,
            $property,
            $extra,
            'tenant'
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(string $memberType, int $propertyLimit = 3): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Billing Landlord '.uniqid(),
            'email' => 'billing-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $memberType,
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = \DB::table('plans')->insertGetId([
            'code' => 'billing-limit-'.uniqid(),
            'name' => 'Billing Limit',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 0,
            'annual_price_minor' => 0,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => $propertyLimit,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => 1,
            'is_active' => 1,
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    /**
     * @return array{0: User}
     */
    private function createMembershipOnAccount(int $accountId, string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' '.uniqid(),
            'email' => strtolower($roleName).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $memberType,
            'access_level' => $memberType === 'staff' ? 'edit' : 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user];
    }
}
