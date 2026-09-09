<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordFinanceHttpTest extends TestCase
{
    public function test_landlord_can_issue_invoice_and_tenant_sees_it_on_rent(): void
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
                'note' => 'September rent',
            ])
            ->assertRedirect();

        $invoice = RentInvoice::query()->forAccount($accountId)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('RENT-0001', $invoice->invoice_no);
        $this->assertEquals(1200.0, (float) $invoice->balance);
        $this->assertSame(RentInvoice::STATUS_ISSUED, $invoice->status);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('RENT-0001', false)
            ->assertSee('1,200.00', false)
            ->assertSee('Issued', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('RENT-0001', false);
    }

    public function test_partial_payment_updates_balance_and_overpay_is_rejected(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 1000,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 400,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
                'reference' => 'REF-1',
            ])
            ->assertRedirect(route('admin.finance.show', $invoice));

        $invoice->refresh();
        $this->assertEquals(600.0, (float) $invoice->balance);
        $this->assertSame(RentInvoice::STATUS_PARTIAL, $invoice->status);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 700,
                'paid_at' => now()->toDateString(),
                'method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 600,
                'paid_at' => now()->toDateString(),
                'method' => 'cash',
            ])
            ->assertRedirect(route('admin.finance.show', $invoice));

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertSame(RentInvoice::STATUS_PAID, $invoice->status);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.void', $invoice))
            ->assertSessionHasErrors('invoice');
    }

    public function test_void_only_works_on_unpaid_invoice(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 500,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.void', $invoice))
            ->assertRedirect(route('admin.finance.show', $invoice));

        $invoice->refresh();
        $this->assertSame(RentInvoice::STATUS_VOID, $invoice->status);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('There are no rent invoices on your account yet', false);
    }

    public function test_landlord_cannot_see_another_accounts_invoice(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();
        [$tenantB] = $this->createTenantOnAccount($accountB);
        [, $tenancyB] = $this->createLet($accountB, $landlordB, $tenantB);

        $this->actingAs($landlordB)->withSession(['current_account_id' => $accountB])
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancyB->id,
                'tenant_user_id' => $tenantB->id,
                'amount' => 800,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoiceB = RentInvoice::query()->forAccount($accountB)->firstOrFail();

        $this->actingAs($landlordA)->withSession(['current_account_id' => $accountA])
            ->get(route('admin.finance.show', $invoiceB))
            ->assertForbidden();

        $this->actingAs($landlordA)->withSession(['current_account_id' => $accountA])
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertDontSee($invoiceB->invoice_no, false);
    }

    public function test_tenant_cannot_open_landlord_finance(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        $this->createLet($accountId, $landlord, $tenant);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.finance.index'))
            ->assertForbidden();

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.finance.create'))
            ->assertForbidden();
    }

    public function test_create_fails_without_billable_tenancy(): void
    {
        [$landlord, $accountId] = $this->createLandlord();

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.finance.store'), [
                'tenancy_id' => 999999,
                'tenant_user_id' => $landlord->id,
                'amount' => 100,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertSessionHasErrors('tenancy_id');
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Finance Landlord',
            'email' => 'finance-landlord-'.uniqid().'@resisquare.test',
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
            'name' => 'Finance Tenant',
            'first_name' => 'Tina',
            'email' => 'finance-tenant-'.uniqid().'@resisquare.test',
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
