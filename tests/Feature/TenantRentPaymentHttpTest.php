<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Finance\RentStripeCheckoutService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRentPaymentHttpTest extends TestCase
{
    public function test_tenant_sees_pay_button_and_is_sent_to_stripe(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 1200,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        config([
            'services.stripe.rent.secret' => 'sk_test_rent',
            'services.stripe.rent.fee_percent' => 1.5,
            'services.stripe.rent.fee_fixed' => 0,
        ]);

        $this->partialMock(RentStripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutUrl')->once()->andReturn('https://checkout.stripe.test/pay-rent');
        });

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Pay £1,218.00', false)
            ->assertSee('Card payment fee', false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertRedirect('https://checkout.stripe.test/pay-rent');
    }

    public function test_tenant_cannot_pay_someone_elses_invoice(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenantA] = $this->createTenantOnAccount($accountId);
        [$tenantB] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenantA);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenantA->id,
                'amount' => 900,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->actingAs($tenantB)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertNotFound();
    }

    public function test_stripe_fulfill_marks_invoice_paid_once(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);

        $invoice = app(\App\Services\Finance\RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1000,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $session = (object) [
            'id' => 'cs_test_rent_'.$invoice->id,
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_rent_'.$invoice->id,
            'metadata' => [
                'type' => 'rent_payment',
                'account_id' => (string) $accountId,
                'rent_invoice_id' => (string) $invoice->id,
                'tenant_user_id' => (string) $tenant->id,
                'rent_amount' => '1000.00',
                'fee_amount' => '15.00',
            ],
        ];

        $checkout = app(RentStripeCheckoutService::class);
        $first = $checkout->fulfillSession($session);
        $second = $checkout->fulfillSession($session);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertEquals(1000.0, (float) $first->amount);
        $this->assertEquals(15.0, (float) $first->fee_amount);
        $this->assertSame(RentPayment::METHOD_CARD, $first->method);

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(RentInvoice::STATUS_PAID, $invoice->status);
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_fee_breakdown_uses_percent_and_fixed(): void
    {
        config([
            'services.stripe.rent.fee_percent' => 1,
            'services.stripe.rent.fee_fixed' => 0.50,
            'services.stripe.rent.fee_label' => 'Card payment fee',
        ]);

        $breakdown = app(RentStripeCheckoutService::class)->feeBreakdown(1000);

        $this->assertEquals(1000.0, $breakdown['rent']);
        $this->assertEquals(10.5, $breakdown['fee']);
        $this->assertEquals(1010.5, $breakdown['total']);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Rent Pay Landlord',
            'email' => 'rent-pay-landlord-'.uniqid().'@resisquare.test',
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
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    /**
     * @return array{0: User}
     */
    private function createTenantOnAccount(int $accountId): array
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Rent Pay Tenant',
            'first_name' => 'Tina',
            'email' => 'rent-pay-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user];
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function createLet(int $accountId, User $landlord, User $tenant): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '10 Rent Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1200,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return [$property, $tenancy];
    }
}
