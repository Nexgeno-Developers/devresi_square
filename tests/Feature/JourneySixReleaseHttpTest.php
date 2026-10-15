<?php

namespace Tests\Feature;

use App\Models\AccountSubscription;
use App\Models\EmailTemplate;
use App\Models\Plan;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\User;
use App\Services\Ops\ProductionOpsConfig;
use App\Services\Saas\ProductionMoneyConfig;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneySixReleaseHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seed_content_is_idempotent_and_creates_no_users(): void
    {
        $users = User::query()->count();

        $this->artisan('launch:seed-content')->assertSuccessful();
        $this->artisan('launch:seed-content')->assertSuccessful();

        $this->assertSame($users, User::query()->count());
        $this->assertSame(1, Plan::query()->where('code', 'landlord_basic')->count());
        $this->assertSame('Landlord Basic', Plan::query()->where('code', 'landlord_basic')->value('name'));

        foreach (['signup_otp', 'tenant_welcome', 'email_change_confirmation', 'contractor_claim', 'repair_invited', 'inspection_accepted', 'repair_awarded', 'repair_not_selected', 'rent_invoice_issued', 'rent_payment_received', 'compliance_expiry', 'team_invite'] as $identifier) {
            $this->assertSame(1, EmailTemplate::query()->whereNull('account_id')->where('identifier', $identifier)->count(), $identifier);
        }

        $otp = EmailTemplate::query()->where('identifier', 'signup_otp')->first();
        $updated = $otp->updated_at?->toDateTimeString();
        $this->artisan('launch:seed-content')->assertSuccessful();
        $this->assertSame($updated, $otp->fresh()->updated_at?->toDateTimeString());
    }

    public function test_production_requires_live_stripe_secrets_and_staging_may_share_test_keys(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'noreply@resisquare.test',
            'queue.default' => 'database',
            'app.debug' => false,
            'services.stripe.secret' => 'sk_test_same',
            'services.stripe.webhook_secret' => 'whsec_op',
            'services.stripe.rent.secret' => 'sk_test_same',
            'services.stripe.rent.webhook_secret' => 'whsec_rent',
        ]);

        $production = app(ProductionMoneyConfig::class)->problems('production');
        $this->assertTrue(collect($production)->contains(fn (string $problem) => str_contains($problem, 'sk_live_')));

        $staging = app(ProductionMoneyConfig::class)->problems('staging');
        $this->assertFalse(collect($staging)->contains(fn (string $problem) => str_contains($problem, 'must not equal')));
        $this->assertFalse(collect($staging)->contains(fn (string $problem) => str_contains($problem, 'sk_live_')));
    }

    public function test_schedule_contains_the_release_commands(): void
    {
        Artisan::call('schedule:list');
        $list = Artisan::output();

        foreach ([
            'rent-invoices:generate-recurring',
            'rent:reconcile-checkouts',
            'crm-notifications:send-due',
            'events:send-reminders',
            'contractors:sweep',
        ] as $command) {
            $this->assertStringContainsString($command, $list);
        }

        $expected = app(ProductionOpsConfig::class)->expectedScheduleSignatures();
        $this->assertContains('contractors:sweep', $expected);
        $this->assertContains('rent:reconcile-checkouts', $expected);
    }

    public function test_stripe_fixtures_activate_once_pay_rent_once_and_mark_past_due(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_operating',
            'services.stripe.webhook_secret' => 'whsec_operating',
            'services.stripe.rent.secret' => 'sk_test_rent',
            'services.stripe.rent.webhook_secret' => 'whsec_rent',
        ]);

        [$landlord, $accountId] = $this->landlord();
        $planId = DB::table('plans')->insertGetId([
            'code' => 'j6-'.uniqid(),
            'name' => 'Journey Six',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 2900,
            'annual_price_minor' => 29000,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => 3,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => 1,
            'is_active' => 1,
            'sort_order' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subscription = AccountSubscription::create([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
        ]);

        $checkout = json_encode([
            'id' => 'evt_j6_checkout',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_j6',
                    'metadata' => [
                        'type' => 'plan_subscription',
                        'account_subscription_id' => (string) $subscription->id,
                        'account_id' => (string) $accountId,
                    ],
                    'subscription' => [
                        'id' => 'sub_j6',
                        'status' => 'active',
                        'cancel_at_period_end' => false,
                        'metadata' => [
                            'account_subscription_id' => (string) $subscription->id,
                            'account_id' => (string) $accountId,
                        ],
                        'items' => ['data' => []],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postStripe('/stripe/webhook', $checkout, 'whsec_operating')->assertOk();
        $this->postStripe('/stripe/webhook', $checkout, 'whsec_operating')->assertOk();
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('active', DB::table('accounts')->where('id', $accountId)->value('status'));
        $this->assertSame('sub_j6', $subscription->fresh()->stripe_subscription_id);

        $failed = json_encode([
            'id' => 'evt_j6_failed',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_j6',
                    'subscription' => 'sub_j6',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $this->postStripe('/stripe/webhook', $failed, 'whsec_operating')->assertOk();
        $this->assertSame('past_due', DB::table('accounts')->where('id', $accountId)->value('status'));

        $property = \App\Models\Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => 'Journey Six '.uniqid(),
            'city' => 'Manchester',
            'postcode' => 'M1 2AB',
        ]);
        $tenancy = \App\Models\Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 100,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        $invoice = RentInvoice::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $landlord->id,
            'invoice_no' => 'RENT-J6',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 100,
            'balance' => 100,
            'status' => RentInvoice::STATUS_ISSUED,
        ]);
        $rent = json_encode([
            'id' => 'evt_j6_rent',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_rent_j6',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'type' => 'rent_payment',
                        'rent_invoice_id' => (string) $invoice->id,
                        'tenant_user_id' => (string) $landlord->id,
                        'rent_amount' => '100',
                        'fee_amount' => '0',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postStripe('/stripe/rent/webhook', $rent, 'whsec_rent')->assertOk();
        $this->postStripe('/stripe/rent/webhook', $rent, 'whsec_rent')->assertOk();
        $this->assertSame(RentInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);
        $this->assertSame(1, RentPayment::query()->where('rent_invoice_id', $invoice->id)->count());
    }

    public function test_debug_off_hides_the_stack_trace(): void
    {
        config(['app.debug' => false]);
        Route::get('/_journey6/boom', function (): void {
            throw new \RuntimeException('SECRET-STACK-MARKER-JOURNEY6');
        });

        $this->get('/_journey6/boom')
            ->assertStatus(500)
            ->assertSee('Something went wrong', false)
            ->assertDontSee('SECRET-STACK-MARKER-JOURNEY6', false);
    }

    private function postStripe(string $url, string $payload, string $secret): \Illuminate\Testing\TestResponse
    {
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->call('POST', $url, [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $payload);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function landlord(): array
    {
        $user = User::create([
            'name' => 'Journey Six',
            'first_name' => 'Journey',
            'email' => 'j6-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));
        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'billing_email' => $user->email,
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

        return [$user, $accountId];
    }
}
