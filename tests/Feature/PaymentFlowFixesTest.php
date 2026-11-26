<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\User;
use App\Services\Saas\StripeCheckoutService;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentFlowFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_forms_may_redirect_to_stripe_hosted_pages(): void
    {
        $policy = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("form-action 'self' https://checkout.stripe.com https://billing.stripe.com;", $policy);
    }

    public function test_a_trial_with_more_than_two_days_left_keeps_its_exact_end(): void
    {
        [, $account, $subscription] = $this->landlordWithSubscription('trialing', ['trial_ends_at' => now()->addDays(5)]);

        $data = $this->checkoutData($account, $subscription)['subscription_data'];

        $this->assertSame($subscription->trial_ends_at->timestamp, $data['trial_end']);
        $this->assertArrayNotHasKey('trial_period_days', $data);
    }

    public function test_a_trial_in_its_last_two_days_can_still_be_activated_without_an_early_charge(): void
    {
        foreach ([30 => 2, 10 => 1] as $hoursLeft => $days) {
            [, $account, $subscription] = $this->landlordWithSubscription('trialing', ['trial_ends_at' => now()->addHours($hoursLeft)]);

            $data = $this->checkoutData($account, $subscription)['subscription_data'];

            $this->assertSame($days, $data['trial_period_days']);
            $this->assertArrayNotHasKey('trial_end', $data);
            $this->assertSame('cancel', $data['trial_settings']['end_behavior']['missing_payment_method']);
        }
    }

    public function test_a_cancelled_or_expired_owner_can_subscribe_again(): void
    {
        foreach (['cancelled', 'expired'] as $status) {
            [$user, $account, $subscription] = $this->landlordWithSubscription($status, [
                'stripe_subscription_id' => 'sub_old_'.uniqid(),
                'cancelled_at' => now(),
            ], 'cancelled');

            $this->owner($user, $account)->get(route('backend.billing.index'))
                ->assertOk()
                ->assertSee('Subscribe again', false);

            $this->mock(StripeCheckoutService::class, function ($mock) use ($subscription) {
                $mock->makePartial();
                $mock->shouldReceive('createPlanCheckoutSession')->once()
                    ->withArgs(fn ($a, AccountSubscription $s) => $s->id === $subscription->id)
                    ->andReturn('https://checkout.stripe.com/c/pay/cs_test_again');
            });

            $this->owner($user, $account)->post(route('backend.saas.subscription.checkout'))
                ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_again');
            $this->app->offsetUnset(StripeCheckoutService::class);
            $this->forgetMock(StripeCheckoutService::class);
        }
    }

    public function test_an_ended_trial_never_reads_as_trialing_on_billing(): void
    {
        [$user, $account] = $this->landlordWithSubscription('trialing', ['trial_ends_at' => now()->subDay()], 'suspended');
        $account->forceFill(['status_reason' => \App\Console\Commands\ExpireEndedTrials::REASON, 'status_changed_by' => null])->save();

        $this->owner($user, $account)->get(route('backend.billing.index'))
            ->assertOk()
            ->assertSee('<tr><th>Status</th><td>Trial ended</td></tr>', false)
            ->assertDontSee('Trialing', false);
    }

    public function test_an_account_closed_by_an_admin_cannot_buy_itself_back(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'pay-admin-'.uniqid().'@resisquare.test', 'password' => bcrypt('password'), 'status' => 1]);
        [$user, $account, $subscription] = $this->landlordWithSubscription('cancelled', [], 'cancelled');
        $account->forceFill(['status_changed_by' => $admin->id, 'status_reason' => 'Closed after review'])->save();

        $this->owner($user, $account)->get(route('backend.billing.index'))
            ->assertOk()
            ->assertDontSee('Subscribe again', false)
            ->assertSee('Please contact support to restore access.', false);
        $this->owner($user, $account)->post(route('backend.saas.subscription.checkout'))->assertRedirect();
        $this->assertNull(app(StripeCheckoutService::class)->renewableSubscription($account->fresh()));

        // Even a completed checkout must not reopen it.
        app(StripeWebhookService::class)->handle($this->event('customer.subscription.created', $this->stripeSubscription($subscription, 'sub_new_'.uniqid(), 'active')));
        $this->assertSame('cancelled', $account->fresh()->status);
    }

    public function test_buying_again_reopens_the_account_and_late_old_events_are_ignored(): void
    {
        $old = 'sub_old_'.uniqid();
        $new = 'sub_new_'.uniqid();
        [, $account, $subscription] = $this->landlordWithSubscription('cancelled', [
            'stripe_subscription_id' => $old,
            'cancelled_at' => now()->subMonth(),
        ], 'cancelled');
        $webhooks = app(StripeWebhookService::class);

        $webhooks->handle($this->event('customer.subscription.created', $this->stripeSubscription($subscription, $new, 'active')));

        $this->assertSame('active', $account->fresh()->status);
        $this->assertSame($new, $subscription->fresh()->stripe_subscription_id);
        $this->assertNull($subscription->fresh()->cancelled_at);

        foreach (['canceled', 'active'] as $status) {
            $webhooks->handle($this->event('customer.subscription.updated', $this->stripeSubscription($subscription, $old, $status)));
            $this->assertSame($new, $subscription->fresh()->stripe_subscription_id);
            $this->assertSame('active', $account->fresh()->status);
        }
    }

    public function test_a_cancellation_billed_by_stripe_drops_an_older_admin_marker(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'pay-admin-'.uniqid().'@resisquare.test', 'password' => bcrypt('password'), 'status' => 1]);
        $id = 'sub_live_'.uniqid();
        [, $account, $subscription] = $this->landlordWithSubscription('active', ['stripe_subscription_id' => $id], 'active');
        $account->forceFill(['status_changed_by' => $admin->id, 'status_reason' => 'Reactivated earlier'])->save();

        app(StripeWebhookService::class)->handle($this->event('customer.subscription.deleted', $this->stripeSubscription($subscription, $id, 'canceled')));

        $this->assertSame('cancelled', $account->fresh()->status);
        $this->assertNull($account->fresh()->status_changed_by);
        $this->assertNotNull(app(StripeCheckoutService::class)->renewableSubscription($account->fresh()));
    }

    public function test_leaving_checkout_does_not_cancel_a_trial_or_a_past_due_account(): void
    {
        foreach (['trialing', 'past_due'] as $status) {
            [, $account, $subscription] = $this->landlordWithSubscription($status, ['trial_ends_at' => now()->addDays(3)], $status);

            app(StripeWebhookService::class)->handle($this->event('checkout.session.expired', [
                'id' => 'cs_left_'.uniqid(),
                'metadata' => ['type' => 'plan_subscription', 'account_id' => (string) $account->id, 'account_subscription_id' => (string) $subscription->id],
            ]));

            $this->assertSame($status, $subscription->fresh()->status);
            $this->assertSame($status, $account->fresh()->status);
        }
    }

    public function test_a_cancellation_scheduled_in_the_portal_is_shown(): void
    {
        $id = 'sub_portal_'.uniqid();
        [, , $subscription] = $this->landlordWithSubscription('active', ['stripe_subscription_id' => $id], 'active');
        $webhooks = app(StripeWebhookService::class);

        $webhooks->handle($this->event('customer.subscription.updated', $this->stripeSubscription($subscription, $id, 'active', ['cancel_at' => now()->addMonth()->timestamp])));
        $this->assertTrue($subscription->fresh()->cancel_at_period_end);

        // "Don't cancel" in the portal clears it again.
        $webhooks->handle($this->event('customer.subscription.updated', $this->stripeSubscription($subscription, $id, 'active', ['cancel_at' => null])));
        $this->assertFalse($subscription->fresh()->cancel_at_period_end);
    }

    /**
     * @return array{0: User, 1: Account, 2: AccountSubscription}
     */
    private function landlordWithSubscription(string $status, array $subscription = [], string $accountStatus = 'trialing'): array
    {
        $user = User::create([
            'name' => 'Pay Landlord',
            'email' => 'pay-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id, 'account_type' => 'landlord', 'status' => $accountStatus,
            'stripe_customer_id' => 'cus_pay_'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('account_users')->insert([
            'account_id' => $accountId, 'user_id' => $user->id, 'member_type' => 'owner', 'access_level' => 'full',
            'can_login' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $planId = \DB::table('plans')->insertGetId([
            'code' => 'pay-plan-'.uniqid(), 'name' => 'Pay Plan', 'target_account_type' => 'landlord',
            'monthly_price_minor' => 2900, 'annual_price_minor' => 29000, 'currency' => 'GBP', 'trial_days' => 7,
            'property_limit' => 5, 'branch_limit' => 0, 'staff_limit' => 0, 'property_manager_limit' => 0,
            'allow_contact_login' => 1, 'is_active' => 1, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $record = AccountSubscription::create($subscription + [
            'account_id' => $accountId, 'plan_id' => $planId, 'billing_cycle' => 'monthly', 'status' => $status,
        ]);

        return [$user, Account::findOrFail($accountId), $record];
    }

    /**
     * The data the app would send to Stripe, without calling Stripe.
     */
    private function checkoutData(Account $account, AccountSubscription $subscription): array
    {
        config(['services.stripe.secret' => 'sk_test_pay']);
        $service = new class extends StripeCheckoutService
        {
            public array $sent = [];

            protected function createStripeSession(array $sessionData): mixed
            {
                $this->sent = $sessionData;

                return (object) ['id' => 'cs_test_pay', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_pay'];
            }
        };
        $service->createPlanCheckoutSession($account, $subscription);

        return $service->sent;
    }

    private function stripeSubscription(AccountSubscription $subscription, string $id, string $status, array $extra = []): array
    {
        return $extra + [
            'id' => $id,
            'status' => $status,
            'cancel_at_period_end' => false,
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => ['type' => 'plan_subscription', 'account_id' => (string) $subscription->account_id, 'account_subscription_id' => (string) $subscription->id],
        ];
    }

    private function event(string $type, array $object): object
    {
        $object = (object) $object;

        return (object) ['id' => 'evt_pay_'.uniqid(), 'type' => $type, 'data' => (object) ['object' => $object]];
    }

    private function owner(User $user, Account $account): static
    {
        return $this->actingAs($user)->withSession(['current_account_id' => $account->id]);
    }
}
