<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Property;
use App\Models\User;
use App\Services\Saas\AccountLimitService;
use App\Services\Saas\PortalAccessService;
use App\Services\Saas\StripeCheckoutService;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaasBillingAndLimitsHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_landlord_owner_and_staff_can_open_billing_and_admin_cannot(): void
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
            ->assertOk();

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
            ->assertSee('Confirming your card', false)
            ->assertSee('Payment processing', false);

        $this->assertSame('past_due', \DB::table('accounts')->where('id', $accountId)->value('status'));
    }

    public function test_checkout_return_unlocks_a_suspended_trial(): void
    {
        Mail::fake();
        [$user, $accountId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);
        $subscriptionId = \DB::table('account_subscriptions')->where('account_id', $accountId)->value('id');

        $this->mock(StripeCheckoutService::class, function ($mock) use ($accountId, $subscriptionId) {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_return')
                ->andReturn(StripeCheckoutSession::constructFrom([
                    'id' => 'cs_test_return',
                    'object' => 'checkout.session',
                    'status' => 'complete',
                    'mode' => 'subscription',
                    'client_reference_id' => (string) $accountId,
                    'metadata' => [
                        'type' => 'plan_subscription',
                        'account_id' => (string) $accountId,
                        'account_subscription_id' => (string) $subscriptionId,
                    ],
                    'subscription' => [
                        'id' => 'sub_test_return',
                        'object' => 'subscription',
                        'status' => 'trialing',
                        'cancel_at_period_end' => false,
                        'metadata' => [
                            'account_id' => (string) $accountId,
                            'account_subscription_id' => (string) $subscriptionId,
                        ],
                        'items' => ['object' => 'list', 'data' => []],
                    ],
                ]));
        });

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.success', ['checkout_ref' => 'cs_test_return']))
            ->assertRedirect(route('backend.billing.index'));

        $this->assertSame('trialing', \DB::table('accounts')->where('id', $accountId)->value('status'));
        $this->assertSame('sub_test_return', \DB::table('account_subscriptions')->where('id', $subscriptionId)->value('stripe_subscription_id'));
    }

    public function test_checkout_return_without_a_query_string_uses_the_stored_session(): void
    {
        Mail::fake();
        [$user, $accountId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);
        $subscriptionId = \DB::table('account_subscriptions')->where('account_id', $accountId)->value('id');

        $this->mock(StripeCheckoutService::class, function ($mock) use ($accountId, $subscriptionId) {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_plain')
                ->andReturn(StripeCheckoutSession::constructFrom([
                    'id' => 'cs_test_plain',
                    'object' => 'checkout.session',
                    'status' => 'complete',
                    'mode' => 'subscription',
                    'client_reference_id' => (string) $accountId,
                    'metadata' => [
                        'type' => 'plan_subscription',
                        'account_id' => (string) $accountId,
                        'account_subscription_id' => (string) $subscriptionId,
                    ],
                    'subscription' => [
                        'id' => 'sub_test_plain',
                        'object' => 'subscription',
                        'status' => 'trialing',
                        'cancel_at_period_end' => false,
                        'metadata' => [
                            'account_id' => (string) $accountId,
                            'account_subscription_id' => (string) $subscriptionId,
                        ],
                        'items' => ['object' => 'list', 'data' => []],
                    ],
                ]));
        });

        $this->actingAs($user)
            ->withSession([
                'current_account_id' => $accountId,
                'billing_checkout_session_id' => 'cs_test_plain',
            ])
            ->get(route('backend.billing.success'))
            ->assertRedirect(route('backend.billing.index'));

        $this->assertSame('trialing', \DB::table('accounts')->where('id', $accountId)->value('status'));
    }

    public function test_checkout_return_ignores_another_accounts_session(): void
    {
        [$user, $accountId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn(StripeCheckoutSession::constructFrom([
                    'id' => 'cs_test_other',
                    'object' => 'checkout.session',
                    'status' => 'complete',
                    'client_reference_id' => '999999',
                    'metadata' => [
                        'type' => 'plan_subscription',
                        'account_id' => '999999',
                    ],
                    'subscription' => [
                        'id' => 'sub_test_other',
                        'object' => 'subscription',
                        'status' => 'active',
                    ],
                ]));
        });

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.billing.success', ['checkout_ref' => 'cs_test_other']))
            ->assertOk()
            ->assertSee('Confirming your card', false)
            ->assertSee('Payment processing', false);

        $this->assertSame('suspended', \DB::table('accounts')->where('id', $accountId)->value('status'));
        $this->assertNull(\DB::table('account_subscriptions')->where('account_id', $accountId)->value('stripe_subscription_id'));
    }

    public function test_suspended_account_can_start_subscription_checkout(): void
    {
        [$user, $accountId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createPlanCheckoutSession')
                ->once()
                ->andReturn('https://checkout.stripe.test/session');
        });

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('backend.saas.subscription.checkout'))
            ->assertRedirect('https://checkout.stripe.test/session');
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
