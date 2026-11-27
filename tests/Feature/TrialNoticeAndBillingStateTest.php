<?php

namespace Tests\Feature;

use App\Console\Commands\ExpireEndedTrials;
use App\Mail\MailManager;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Saas\TrialEndingNotice;
use App\Support\SubscriptionBillingState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrialNoticeAndBillingStateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // A fixed morning, so "today" and "tomorrow" do not depend on when the suite runs.
        Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_signup_shows_the_trial_and_automatic_payment_terms_before_the_code_is_sent(): void
    {
        $planId = $this->plan(['trial_days' => 7]);

        $this->get(route('register', ['plan_id' => $planId, 'billing_cycle' => 'monthly', 'account_type' => 'landlord']))
            ->assertOk()
            ->assertSee('id="trial-terms-modal"', false)
            ->assertSee('Your 7-day free trial and automatic payment', false)
            ->assertSee('About 17 Jun 2026')
            ->assertSee('£29.00 every month until you cancel')
            ->assertSee('If a payment fails.', false)
            ->assertSee('id="trial-terms-accept"', false)
            ->assertSee('Agree and send code');
    }

    public function test_signup_terms_follow_the_yearly_price_and_a_plan_without_a_trial(): void
    {
        $planId = $this->plan(['trial_days' => 0]);

        $this->get(route('register', ['plan_id' => $planId, 'billing_cycle' => 'annual', 'account_type' => 'landlord']))
            ->assertOk()
            ->assertSee('Your subscription and automatic payment', false)
            ->assertSee('£290.00 every year until you cancel')
            ->assertDontSee('If you do not add a card.', false);
    }

    public function test_the_reminder_goes_once_by_email_and_in_app_the_day_before(): void
    {
        [$user, $account] = $this->landlord('trialing', now()->addDay()->setTime(17, 30));

        $this->artisan('accounts:trial-expiry-warning', ['--account' => [$account->id]])
            ->expectsOutputToContain('Trial expiry warnings sent: 1.')
            ->assertSuccessful();
        $this->artisan('accounts:trial-expiry-warning', ['--account' => [$account->id]])
            ->expectsOutputToContain('Already told: 1.')
            ->assertSuccessful();

        Mail::assertSent(MailManager::class, 1);
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($user->email));

        $logs = NotificationLog::query()->where('account_id', $account->id)->where('identifier', TrialEndingNotice::IDENTIFIER)->get();
        $this->assertEqualsCanonicalizing(['email', 'system'], $logs->pluck('channel')->all());
        $this->assertSame(['sent'], $logs->pluck('status')->unique()->values()->all());
        $this->assertSame('Your ResiSquare free trial ends tomorrow (11 June 2026)', $logs->firstWhere('channel', 'email')->subject);

        $bells = DB::table('notifications')->where('notifiable_id', $user->id)->where('event_key', TrialEndingNotice::IDENTIFIER)->get();
        $this->assertCount(1, $bells);
        $this->assertSame($account->id, (int) $bells[0]->account_id);
        $this->assertSame(route('backend.billing.index'), $bells[0]->action_url);
        $this->assertStringContainsString('Your free trial ends tomorrow', $bells[0]->data);
    }

    public function test_only_trials_ending_by_tomorrow_are_told(): void
    {
        [, $later] = $this->landlord('trialing', now()->addDays(2)->setTime(9, 0));
        [, $over] = $this->landlord('trialing', now()->subHour());
        [, $paid] = $this->landlord('active', now()->addDay());
        [, $today] = $this->landlord('trialing', now()->addHours(3));

        $this->artisan('accounts:trial-expiry-warning', ['--account' => [$later->id, $over->id, $paid->id, $today->id]])
            ->expectsOutputToContain('Trial expiry warnings sent: 1.')
            ->assertSuccessful();

        $this->assertSame(
            [$today->id],
            NotificationLog::query()->where('identifier', TrialEndingNotice::IDENTIFIER)
                ->whereIn('account_id', [$later->id, $over->id, $paid->id, $today->id])
                ->pluck('account_id')->unique()->values()->all()
        );
    }

    public function test_a_dry_run_sends_nothing_and_an_extended_trial_is_told_again(): void
    {
        [, $account] = $this->landlord('trialing', now()->addDay());

        $this->artisan('accounts:trial-expiry-warning', ['--dry-run' => true, '--account' => [$account->id]])
            ->expectsOutputToContain('1 account would be told.')
            ->assertSuccessful();
        Mail::assertNothingSent();

        $notice = app(TrialEndingNotice::class);
        $this->assertSame('sent', $notice->send($account->fresh()));
        $account->forceFill(['trial_ends_at' => now()->addDay()->addHour()])->save();
        $this->assertSame('sent', $notice->send($account->fresh()));
        Mail::assertSent(MailManager::class, 2);
    }

    public function test_the_reminder_says_what_will_happen_to_this_account(): void
    {
        $notice = app(TrialEndingNotice::class);
        [, $noCard] = $this->landlord('trialing', now()->addDay());
        [, $card] = $this->landlord('trialing', now()->addDay(), ['stripe_subscription_id' => 'sub_card_'.uniqid()]);
        [, $cancelled] = $this->landlord('trialing', now()->addDay(), ['stripe_subscription_id' => 'sub_stop_'.uniqid(), 'cancel_at_period_end' => true]);

        $this->assertStringContainsString('No card is saved yet', $notice->message($noCard)['email']);
        $this->assertStringContainsString('we will charge £29.00 a month', $notice->message($card)['email']);
        $this->assertStringContainsString('will not be charged', $notice->message($cancelled)['email']);
    }

    public function test_a_failed_email_stays_in_the_log_for_retry_and_keeps_the_in_app_notice(): void
    {
        [$user, $account] = $this->landlord('trialing', now()->addDay());
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Mailbox offline'));

        $this->assertSame('failed', app(TrialEndingNotice::class)->send($account));

        $email = NotificationLog::query()->where('account_id', $account->id)->where('channel', 'email')->firstOrFail();
        $this->assertSame('failed', $email->status);
        $this->assertSame(1, (int) $email->attempt);
        $this->assertLessThan((int) $email->max_attempts, (int) $email->attempt);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $user->id)->where('event_key', TrialEndingNotice::IDENTIFIER)->count());
        // Retries remain, so nobody is told delivery has given up.
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->where('event_key', 'system.delivery_failed')->count());
    }

    public function test_each_stage_of_trial_and_purchase_is_named(): void
    {
        $end = now()->addDays(3);
        $cases = [
            'trial_no_card' => ['trialing', $end, []],
            'trial_card' => ['trialing', $end, ['stripe_subscription_id' => 'sub_a_'.uniqid()]],
            'trial_cancelling' => ['trialing', $end, ['stripe_subscription_id' => 'sub_b_'.uniqid(), 'cancel_at_period_end' => true]],
            'trial_expired' => ['trialing', now()->subDay(), []],
            'awaiting_stripe' => ['trialing', now()->subDay(), ['stripe_subscription_id' => 'sub_c_'.uniqid()]],
            'subscribed' => ['active', null, ['status' => 'active', 'stripe_subscription_id' => 'sub_d_'.uniqid(), 'current_period_end' => now()->addMonth()]],
            'cancelling' => ['active', null, ['status' => 'active', 'stripe_subscription_id' => 'sub_e_'.uniqid(), 'cancel_at_period_end' => true, 'current_period_end' => now()->addMonth()]],
            'active_unpaid' => ['active', null, ['status' => 'active']],
            'payment_failed' => ['past_due', null, ['status' => 'past_due', 'stripe_subscription_id' => 'sub_f_'.uniqid()]],
            'cancelled' => ['cancelled', null, ['status' => 'cancelled', 'cancelled_at' => now()]],
        ];

        foreach ($cases as $expected => [$accountStatus, $trialEnds, $subscription]) {
            [, $account] = $this->landlord($accountStatus, $trialEnds, $subscription);
            $this->assertSame($expected, SubscriptionBillingState::for($account)['key']);
        }

        [, $expired] = $this->landlord('suspended', now()->subDay());
        $expired->forceFill(['status_reason' => ExpireEndedTrials::REASON])->save();
        $this->assertSame('trial_expired', SubscriptionBillingState::for($expired->fresh())['key']);
    }

    public function test_super_admin_sees_and_filters_trial_and_purchase_status(): void
    {
        [, $waiting, $waitingSubscription] = $this->landlord('trialing', now()->addDays(3));
        [, $bought] = $this->landlord('trialing', now()->addDays(3), ['stripe_subscription_id' => 'sub_admin_'.uniqid()]);
        [, $expired] = $this->landlord('suspended', now()->subDay());
        $expired->forceFill(['status_reason' => ExpireEndedTrials::REASON])->save();

        $admin = User::create(['name' => 'Trial Admin', 'email' => 'trial-admin-'.uniqid().'@resisquare.test', 'password' => bcrypt('password'), 'status' => 1]);
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->actingAs($admin);

        $this->get(route('backend.saas.accounts.index'))
            ->assertOk()
            ->assertSee('data-billing-group="trial_expired"', false)
            ->assertSee('On trial, purchased')
            ->assertSee('Trial expired, not purchased');

        $this->get(route('backend.saas.accounts.index', ['billing_state' => 'trial_card']))
            ->assertOk()
            ->assertSee(route('backend.saas.accounts.show', $bought->id), false)
            ->assertDontSee(route('backend.saas.accounts.show', $waiting->id).'"', false)
            ->assertDontSee(route('backend.saas.accounts.show', $expired->id).'"', false);

        $this->get(route('backend.saas.accounts.show', $expired->id))
            ->assertOk()
            ->assertSee('data-billing-state="trial_expired"', false);
        $this->get(route('backend.saas.subscriptions.index'))
            ->assertOk()
            ->assertSee('data-billing-state="trial_no_card"', false);
        $this->get(route('backend.saas.subscriptions.show', $waitingSubscription->id))
            ->assertOk()
            ->assertSee('data-billing-state="trial_no_card"', false);
        $this->get(route('backend.saas.billing.index'))
            ->assertOk()
            ->assertSee('data-billing-state=', false);
        $this->get(route('backend.dashboard'))->assertOk();
    }

    private function plan(array $overrides = []): int
    {
        return DB::table('plans')->insertGetId($overrides + [
            'code' => 'notice-plan-'.uniqid(), 'name' => 'Notice Plan', 'target_account_type' => 'landlord',
            'monthly_price_minor' => 2900, 'annual_price_minor' => 29000, 'currency' => 'GBP', 'trial_days' => 7,
            'property_limit' => 5, 'branch_limit' => 0, 'staff_limit' => 0, 'property_manager_limit' => 0,
            'allow_contact_login' => 1, 'is_active' => 1, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: User, 1: Account, 2: AccountSubscription}
     */
    private function landlord(string $accountStatus, ?Carbon $trialEnds, array $subscription = []): array
    {
        $user = User::create([
            'name' => 'Notice Landlord',
            'email' => 'notice-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id, 'account_type' => 'landlord', 'account_name' => 'Notice <Lettings>',
            'status' => $accountStatus, 'trial_ends_at' => $trialEnds, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('account_users')->insert([
            'account_id' => $accountId, 'user_id' => $user->id, 'member_type' => 'owner', 'access_level' => 'full',
            'can_login' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $record = AccountSubscription::create($subscription + [
            'account_id' => $accountId, 'plan_id' => $this->plan(), 'billing_cycle' => 'monthly',
            'status' => 'trialing', 'trial_ends_at' => $trialEnds,
        ]);

        return [$user, Account::findOrFail($accountId), $record];
    }
}
