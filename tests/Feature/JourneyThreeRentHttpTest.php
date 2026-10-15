<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Finance\RentFinanceService;
use App\Services\Finance\RentStripeCheckoutService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneyThreeRentHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_housemate_can_see_the_invoice_but_only_the_named_payer_can_start_checkout(): void
    {
        [$landlord, $accountId] = $this->createLandlord('Payer');
        $payer = $this->createTenant($accountId, 'Named Payer');
        $housemate = $this->createTenant($accountId, 'Housemate');
        [, $tenancy] = $this->createLet($accountId, $landlord, $payer, $housemate);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.rent-pay.bank'), [
                'bank_name' => 'Household Bank',
                'account_name' => 'Household Landlord',
                'sort_code' => '11-22-33',
                'account_no' => '44556677',
            ]);

        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $payer->id,
            'amount' => 1200,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        config([
            'services.stripe.rent.secret' => 'sk_test_rent',
            'services.stripe.rent.fee_percent' => 0,
            'services.stripe.rent.fee_fixed' => 0,
        ]);

        $this->actingAs($housemate)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee($invoice->invoice_no, false)
            ->assertSee('44556677', false)
            ->assertDontSee(route('tenant.rent.pay', $invoice), false);

        $this->actingAs($housemate)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertForbidden();

        $this->partialMock(RentStripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutUrl')->once()->andReturn('https://checkout.stripe.test/named-payer');
        });

        $this->actingAs($payer)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertRedirect('https://checkout.stripe.test/named-payer');

        $invoice->refresh();
        $this->assertSame(RentInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertEquals(1200.0, (float) $invoice->balance);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_a_webhook_pays_an_invoice_once(): void
    {
        [$invoice, $tenant] = $this->issuedInvoice(1000);
        $checkout = app(RentStripeCheckoutService::class);
        $session = $this->paidSession($invoice, $tenant, '1000.00', 'cs_once');

        $first = $checkout->fulfillSession($session);
        $second = $checkout->fulfillSession($session);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_a_late_webhook_does_not_pay_an_invoice_again_after_a_manual_payment(): void
    {
        [$landlord, $accountId, $invoice, $tenant] = $this->issuedInvoiceWithLandlord(800);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 800,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
            ])
            ->assertRedirect();

        $this->assertNull(app(RentStripeCheckoutService::class)->fulfillSession(
            $this->paidSession($invoice, $tenant, '800.00', 'cs_late')
        ));

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame('bank_transfer', $invoice->payments()->first()->method);
    }

    public function test_manual_pay_is_rejected_after_the_webhook_has_paid_the_invoice(): void
    {
        [$landlord, $accountId, $invoice, $tenant] = $this->issuedInvoiceWithLandlord(640);

        $this->assertNotNull(app(RentStripeCheckoutService::class)->fulfillSession(
            $this->paidSession($invoice, $tenant, '640.00', 'cs_full')
        ));

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 10,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
            ])
            ->assertSessionHasErrors('amount');

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_a_partial_manual_payment_leaves_the_remainder_payable_by_card(): void
    {
        [$landlord, $accountId, $invoice, $tenant] = $this->issuedInvoiceWithLandlord(1000);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 400,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
            ])
            ->assertRedirect();

        $this->assertNotNull(app(RentStripeCheckoutService::class)->fulfillSession(
            $this->paidSession($invoice->fresh(), $tenant, '600.00', 'cs_rest')
        ));

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(RentInvoice::STATUS_PAID, $invoice->status);
        $this->assertSame(2, $invoice->payments()->count());
        $this->assertEquals(1000.0, (float) $invoice->payments()->sum('amount'));
    }

    public function test_a_void_invoice_cannot_be_paid(): void
    {
        [$landlord, $accountId, $invoice, $tenant] = $this->issuedInvoiceWithLandlord(300);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.finance.void', $invoice))
            ->assertRedirect();

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.rent.pay', $invoice))
            ->assertNotFound();

        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_recurring_catch_up_issues_missed_months_and_keeps_the_next_period(): void
    {
        [$landlord, $accountId] = $this->createLandlord('Catchup');
        $tenant = $this->createTenant($accountId, 'Catchup Tenant');
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $tenancy->update([
            'rent' => 900,
            'frequency' => 'Monthly',
            'rent_auto_invoice' => true,
            'rent_auto_invoice_tenant_user_id' => $tenant->id,
            'rent_next_period_start' => '2026-01-01',
        ]);

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-03-31'])->assertSuccessful();

        $this->assertSame(3, RentInvoice::query()->where('tenancy_id', $tenancy->id)->count());
        $this->assertSame('2026-04-01', $tenancy->fresh()->rent_next_period_start->toDateString());

        [, $capped] = $this->createLet($accountId, $landlord, $tenant);
        $capped->update([
            'rent' => 900,
            'frequency' => 'Monthly',
            'rent_auto_invoice' => true,
            'rent_auto_invoice_tenant_user_id' => $tenant->id,
            'rent_next_period_start' => '2024-01-01',
        ]);
        $cappedNotices = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$cappedNotices, $capped) {
            if (($event->context['tenancy_id'] ?? null) === $capped->id) {
                $cappedNotices[] = $event;
            }
        });

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-01-01'])->assertSuccessful();

        $this->assertSame(24, RentInvoice::query()->where('tenancy_id', $capped->id)->count());
        $this->assertSame('2026-01-01', $capped->fresh()->rent_next_period_start->toDateString());
        $this->assertNotEmpty($cappedNotices);
        $this->assertSame('2026-01-01', $cappedNotices[0]->context['rent_next_period_start'] ?? null);

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-01-01'])->assertSuccessful();

        $this->assertSame(25, RentInvoice::query()->where('tenancy_id', $capped->id)->count());
        $this->assertTrue(
            RentInvoice::query()->where('tenancy_id', $capped->id)->whereDate('period_start', '2026-01-01')->exists()
        );
    }

    public function test_changing_the_main_tenant_does_not_rewrite_the_open_invoice(): void
    {
        [$landlord, $accountId] = $this->createLandlord('Main');
        $payer = $this->createTenant($accountId, 'Original Payer');
        $housemate = $this->createTenant($accountId, 'New Main');
        [, $tenancy] = $this->createLet($accountId, $landlord, $payer, $housemate);
        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $payer->id,
            'amount' => 700,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        TenantMember::query()->where('tenancy_id', $tenancy->id)->where('user_id', $payer->id)->update(['is_main_person' => false]);
        TenantMember::query()->where('tenancy_id', $tenancy->id)->where('user_id', $housemate->id)->update(['is_main_person' => true]);

        $this->assertSame($payer->id, (int) $invoice->fresh()->tenant_user_id);
    }

    public function test_suspended_account_refuses_card_pay_and_past_due_still_opens_the_portfolio(): void
    {
        [$landlord, $accountId] = $this->createLandlord('Status');
        $tenant = $this->createTenant($accountId, 'Status Tenant');
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.rent-pay.bank'), [
                'bank_name' => 'Pause Bank',
                'account_name' => 'Pause Landlord',
                'sort_code' => '20-30-40',
                'account_no' => '99887766',
            ]);

        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 450,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        config(['services.stripe.rent.secret' => 'sk_test_rent']);
        DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('99887766', false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertRedirect(route('tenant.rent'));

        $this->assertSame(RentInvoice::STATUS_ISSUED, $invoice->fresh()->status);

        DB::table('accounts')->where('id', $accountId)->update(['status' => 'past_due']);

        $properties = $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.properties.index'));
        if ($properties->status() === 302) {
            $properties = $this->actingAs($landlord)->withSession($session)
                ->get($properties->headers->get('Location'));
        }
        $properties->assertOk();

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.billing.index'))
            ->assertOk();
    }

    /**
     * @return array{0: RentInvoice, 1: User}
     */
    private function issuedInvoice(float $amount): array
    {
        [, , $invoice, $tenant] = $this->issuedInvoiceWithLandlord($amount);

        return [$invoice, $tenant];
    }

    /**
     * @return array{0: User, 1: int, 2: RentInvoice, 3: User}
     */
    private function issuedInvoiceWithLandlord(float $amount): array
    {
        [$landlord, $accountId] = $this->createLandlord('Ledger');
        $tenant = $this->createTenant($accountId, 'Ledger Tenant');
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => $amount,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        return [$landlord, $accountId, $invoice, $tenant];
    }

    private function paidSession(RentInvoice $invoice, User $tenant, string $rentAmount, string $id): object
    {
        return (object) [
            'id' => $id.'_'.$invoice->id,
            'payment_status' => 'paid',
            'payment_intent' => 'pi_'.$id,
            'metadata' => [
                'type' => 'rent_payment',
                'account_id' => (string) $invoice->account_id,
                'rent_invoice_id' => (string) $invoice->id,
                'tenant_user_id' => (string) $tenant->id,
                'rent_amount' => $rentAmount,
                'fee_amount' => '0.00',
            ],
        ];
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(string $label): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => $label.' Landlord',
            'email' => strtolower(str_replace(' ', '-', $label)).'-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = (int) DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
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

    private function createTenant(int $accountId, string $name): User
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function createLet(int $accountId, User $landlord, User $payer, ?User $housemate = null): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => 'Rent House '.uniqid(),
            'city' => 'London',
            'postcode' => 'SW1A 2AA',
        ]);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1200,
            'frequency' => 'Monthly',
            'move_in' => '2024-01-01',
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $payer->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);
        if ($housemate) {
            TenantMember::create([
                'account_id' => $accountId,
                'tenancy_id' => $tenancy->id,
                'user_id' => $housemate->id,
                'is_main_person' => false,
                'can_login' => true,
            ]);
        }

        return [$property, $tenancy];
    }
}
