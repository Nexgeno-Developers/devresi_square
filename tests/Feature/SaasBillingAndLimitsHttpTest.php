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

    public function test_a_billing_webhook_does_not_lift_an_admin_suspension(): void
    {
        Mail::fake();
        [$owner, $accountId] = $this->createLandlord('owner');
        $subscriptionId = \DB::table('account_subscriptions')->where('account_id', $accountId)->value('id');
        \DB::table('account_subscriptions')->where('id', $subscriptionId)->update(['status' => 'active', 'stripe_subscription_id' => 'sub_held_'.$accountId]);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'active']);

        $this->actingAs($this->platformAdmin())
            ->post(route('backend.saas.accounts.status', $accountId), ['action' => 'suspend', 'reason' => 'Chargeback under review'])
            ->assertRedirect();
        $this->assertSame('suspended', Account::find($accountId)->status);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $accountId, 'action' => 'account_status_suspend']);

        // A renewal arrives from Stripe while the account is suspended.
        app(StripeWebhookService::class)->handle((object) [
            'id' => 'evt_held_'.uniqid(),
            'type' => 'customer.subscription.updated',
            'data' => (object) ['object' => (object) [
                'id' => 'sub_held_'.$accountId,
                'object' => 'subscription',
                'status' => 'active',
                'cancel_at_period_end' => false,
                'metadata' => [],
                'items' => (object) ['object' => 'list', 'data' => []],
            ]],
        ]);

        $this->assertSame('suspended', Account::find($accountId)->status);
        $this->assertSame('active', \DB::table('account_subscriptions')->where('id', $subscriptionId)->value('status'));

        // The owner is told to contact support, not to pay.
        $this->actingAs($owner)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertRedirect(route('backend.billing.index'));
    }

    public function test_reactivate_follows_the_subscription_and_refuses_free_access(): void
    {
        [, $accountId] = $this->createLandlord('owner');
        $admin = $this->platformAdmin();
        \DB::table('account_subscriptions')->where('account_id', $accountId)->update(['status' => 'past_due']);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'past_due', 'trial_ends_at' => now()->subDays(3)]);

        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'suspend', 'reason' => 'Card failed twice']);
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'reactivate', 'reason' => 'Owner asked to come back']);
        $this->assertSame('past_due', Account::find($accountId)->status);

        // With no live subscription and the trial over, reactivating is refused.
        \DB::table('account_subscriptions')->where('account_id', $accountId)->update(['status' => 'cancelled']);
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'suspend', 'reason' => 'Card failed twice']);
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'reactivate', 'reason' => 'Owner asked to come back']);
        $this->assertSame('suspended', Account::find($accountId)->status);
    }

    public function test_admin_cancel_stops_stripe_billing_first_and_changes_nothing_if_stripe_fails(): void
    {
        [, $accountId] = $this->createLandlord('owner');
        \DB::table('account_subscriptions')->where('account_id', $accountId)->update(['status' => 'active', 'stripe_subscription_id' => 'sub_cancel_'.$accountId]);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'active', 'stripe_customer_id' => 'cus_cancel_'.$accountId]);
        $admin = $this->platformAdmin();

        // Stripe refuses: the account and subscription stay as they were.
        $this->mock(StripeCheckoutService::class, fn ($mock) => $mock->shouldReceive('cancelAllBillingNow')->once()->andThrow(new \RuntimeException('Stripe is down')));
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'cancel', 'reason' => 'Owner asked to close'])
            ->assertRedirect();
        $this->assertSame('active', Account::find($accountId)->status);
        $this->assertSame('active', \DB::table('account_subscriptions')->where('account_id', $accountId)->value('status'));

        // Stripe cancels: then the account is cancelled, and the audit entry names what Stripe stopped.
        $this->mock(StripeCheckoutService::class, fn ($mock) => $mock->shouldReceive('cancelAllBillingNow')->once()
            ->withArgs(fn (Account $account) => (int) $account->id === $accountId)
            ->andReturn(['sub_cancel_'.$accountId]));
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $accountId), ['action' => 'cancel', 'reason' => 'Owner asked to close'])
            ->assertRedirect();
        $this->assertSame('cancelled', Account::find($accountId)->status);
        $this->assertSame('cancelled', \DB::table('account_subscriptions')->where('account_id', $accountId)->value('status'));
        $this->assertStringContainsString('sub_cancel_'.$accountId, (string) \DB::table('audit_events')
            ->where('subject_id', $accountId)->where('action', 'account_status_cancel')->value('after'));
    }

    public function test_admin_cancel_of_a_trial_without_stripe_does_not_call_stripe(): void
    {
        [, $accountId] = $this->createLandlord('owner');
        $this->mock(StripeCheckoutService::class, fn ($mock) => $mock->shouldNotReceive('cancelAllBillingNow'));

        $this->actingAs($this->platformAdmin())->post(route('backend.saas.accounts.status', $accountId), ['action' => 'cancel', 'reason' => 'Trial never used'])
            ->assertRedirect();
        $this->assertSame('cancelled', Account::find($accountId)->status);
    }

    public function test_trial_extension_is_audited_and_an_older_subscription_does_not_reset_the_account(): void
    {
        [, $accountId] = $this->createLandlord('owner');
        $trial = \App\Models\AccountSubscription::query()->where('account_id', $accountId)->firstOrFail();
        $admin = $this->platformAdmin();

        $this->actingAs($admin)->post(route('backend.saas.subscriptions.extend-trial', $trial), ['additional_days' => 10])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_events', ['subject_id' => $trial->id, 'action' => 'subscription_trial_extended', 'actor_user_id' => $admin->id]);
        $this->assertSame('trialing', Account::find($accountId)->status);

        // The account has since moved to a newer, paid subscription. Extending the old trial leaves it active.
        $trial->forceFill(['created_at' => now()->subDays(30)])->save();
        \App\Models\AccountSubscription::forceCreate([
            'account_id' => $accountId,
            'plan_id' => $trial->plan_id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'currency_at_signup' => 'GBP',
        ]);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'active']);

        $this->actingAs($admin)->post(route('backend.saas.subscriptions.extend-trial', $trial), ['additional_days' => 5]);
        $this->assertSame('active', Account::find($accountId)->status);
    }

    public function test_ended_trials_are_suspended_until_the_owner_pays_or_an_admin_extends(): void
    {
        [$owner, $lapsedId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $lapsedId)->update(['trial_ends_at' => now()->subDay()]);
        \DB::table('account_subscriptions')->where('account_id', $lapsedId)->update(['trial_ends_at' => now()->subDay()]);

        [, $runningId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $runningId)->update(['trial_ends_at' => now()->addDays(3)]);

        [, $stripeId] = $this->createLandlord('owner');
        \DB::table('accounts')->where('id', $stripeId)->update(['trial_ends_at' => now()->subDay()]);
        \DB::table('account_subscriptions')->where('account_id', $stripeId)->update(['stripe_subscription_id' => 'sub_stripe_trial_'.$stripeId]);

        $this->artisan('accounts:expire-trials')->assertSuccessful();

        $this->assertSame('suspended', Account::find($lapsedId)->status);
        $this->assertSame('Trial ended', Account::find($lapsedId)->status_reason);
        $this->assertNull(Account::find($lapsedId)->status_changed_by);
        $this->assertSame('trialing', \DB::table('account_subscriptions')->where('account_id', $lapsedId)->value('status'));
        $this->assertSame('trialing', Account::find($runningId)->status);
        $this->assertSame('trialing', Account::find($stripeId)->status);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $lapsedId, 'action' => 'account_trial_expired']);

        // The owner is sent to Billing & Plan to choose a plan.
        $this->actingAs($owner)->withSession(['current_account_id' => $lapsedId])
            ->get(route('backend.dashboard'))
            ->assertRedirect(route('backend.billing.index'));

        // Running it again changes nothing more.
        $this->artisan('accounts:expire-trials')->expectsOutputToContain('0 ended trials suspended')->assertSuccessful();

        // An admin cannot "reactivate" onto a trial that is over, but extending the trial unlocks it.
        $admin = $this->platformAdmin();
        $this->actingAs($admin)->post(route('backend.saas.accounts.status', $lapsedId), ['action' => 'reactivate', 'reason' => 'Owner asked for more time']);
        $this->assertSame('suspended', Account::find($lapsedId)->status);
        $subscription = \App\Models\AccountSubscription::query()->where('account_id', $lapsedId)->firstOrFail();
        $this->actingAs($admin)->post(route('backend.saas.subscriptions.extend-trial', $subscription), ['additional_days' => 7]);
        $this->assertSame('trialing', Account::find($lapsedId)->status);
        $this->assertNull(Account::find($lapsedId)->status_reason);
    }

    public function test_plan_and_addon_stripe_prices_must_match_the_app_price(): void
    {
        $admin = $this->platformAdmin();
        $plan = [
            'code' => 'qa_price_check_'.uniqid(),
            'name' => 'QA price check',
            'target_account_type' => 'landlord',
            'monthly_price' => '29',
            'annual_price' => '290',
            'currency' => 'GBP',
            'stripe_monthly_price_id' => 'price_wrongAmount',
            'trial_days' => 7,
            'property_limit' => 1, 'branch_limit' => 0, 'staff_limit' => 0, 'property_manager_limit' => 0,
            'sort_order' => 0,
        ];

        $this->mock(\App\Services\Saas\StripePriceCheck::class, fn ($mock) => $mock->shouldReceive('problem')
            ->with('price_wrongAmount', 2900, 'GBP', 'month')->andReturn('Stripe price price_wrongAmount charges 15.00'));
        $this->actingAs($admin)->post(route('backend.saas.plans.store'), $plan)->assertSessionHasErrors('stripe_monthly_price_id');
        $this->assertDatabaseMissing('plans', ['code' => $plan['code']]);

        $this->actingAs($admin)->post(route('backend.saas.plans.store'), ['trial_days' => 731, 'stripe_monthly_price_id' => ''] + $plan)
            ->assertSessionHasErrors('trial_days');

        $this->mock(\App\Services\Saas\StripePriceCheck::class, fn ($mock) => $mock->shouldReceive('problem')->andReturnNull());
        $this->actingAs($admin)->post(route('backend.saas.plans.store'), ['stripe_monthly_price_id' => 'price_rightAmount'] + $plan)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('plans', ['code' => $plan['code'], 'stripe_monthly_price_id' => 'price_rightAmount']);

        $this->actingAs($admin)->post(route('backend.saas.addons.store'), [
            'code' => 'qa_bad_price_'.uniqid(), 'name' => 'QA', 'addon_type' => 'staff', 'monthly_price' => 1, 'annual_price' => 10,
            'currency' => 'GBP', 'grant_quantity' => 1, 'stripe_monthly_price_id' => 'not a price',
        ])->assertSessionHasErrors('stripe_monthly_price_id');
    }

    public function test_paid_addons_keep_their_limits_when_the_catalogue_addon_is_changed(): void
    {
        [$owner, $accountId] = $this->createLandlord('owner', 1);
        $account = Account::find($accountId);
        $subscription = \App\Models\AccountSubscription::query()->where('account_id', $accountId)->firstOrFail();
        $addon = \App\Models\Addon::create([
            'code' => 'qa_paid_extra_'.uniqid(), 'name' => 'QA extra property', 'addon_type' => 'property',
            'monthly_price_minor' => 500, 'annual_price_minor' => 5000, 'currency' => 'GBP',
            'grant_quantity' => 1, 'is_stackable' => true, 'is_active' => true,
        ]);
        \App\Models\AccountSubscriptionAddon::create([
            'account_subscription_id' => $subscription->id, 'account_id' => $accountId, 'addon_id' => $addon->id,
            'quantity' => 2, 'billing_cycle' => 'monthly', 'status' => 'active',
        ]);
        $limits = app(AccountLimitService::class);
        $before = $limits->getPropertyLimit($account);

        $admin = $this->platformAdmin();
        $form = ['code' => $addon->code, 'name' => $addon->name, 'addon_type' => 'property', 'monthly_price' => '5', 'annual_price' => '50', 'currency' => 'GBP', 'grant_quantity' => 1];

        // Taking it off sale keeps what customers already pay for.
        $this->actingAs($admin)->put(route('backend.saas.addons.update', $addon), $form + ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($addon->fresh()->is_active);
        $this->assertSame($before, $limits->getPropertyLimit($account->fresh()));

        // Changing what it grants is refused while customers hold it.
        $this->actingAs($admin)->put(route('backend.saas.addons.update', $addon), ['grant_quantity' => 5] + $form)
            ->assertSessionHasErrors('grant_quantity');
        $this->assertSame(1, (int) $addon->fresh()->grant_quantity);
    }

    private function platformAdmin(): User
    {
        $user = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));

        return $user;
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

    public function test_webhook_that_fails_is_processed_again_on_retry(): void
    {
        $eventId = 'evt_retry_'.uniqid();
        $broken = new class
        {
            public function __isset(string $name): bool
            {
                return true;
            }

            public function __get(string $name): mixed
            {
                throw new \RuntimeException('Simulated processing failure');
            }
        };
        $event = (object) [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => (object) ['object' => $broken],
        ];

        $service = app(StripeWebhookService::class);

        try {
            $service->handle($event);
            $this->fail('The simulated failure should reach the caller.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated processing failure', $exception->getMessage());
        }

        $this->assertFalse(Cache::has('stripe_webhook_event:'.$eventId));

        $retry = (object) [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => (object) ['object' => (object) ['metadata' => ['type' => 'other']]],
        ];
        $service->handle($retry);

        $this->assertTrue(Cache::has('stripe_webhook_event:'.$eventId));
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
