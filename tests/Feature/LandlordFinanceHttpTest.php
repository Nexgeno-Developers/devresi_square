<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\NotificationLog;
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
            ->assertSee('Unpaid', false);

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

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('600.00', false)
            ->assertSee('Part paid', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.show', $invoice))
            ->assertOk()
            ->assertSee('600.00', false);

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
            ->assertSee('Your landlord has not sent a rent invoice yet', false);
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

    public function test_overdue_invoice_shows_on_finance_and_dashboard(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 900,
                'issue_date' => now()->subDays(20)->toDateString(),
                'due_date' => now()->subDays(5)->toDateString(),
            ])
            ->assertRedirect();

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('overdue rent invoice', false)
            ->assertSee('Overdue', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertSee($invoice->invoice_no, false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('overdue rent invoice', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.rent-ledger', $tenancy->id))
            ->assertOk()
            ->assertSee($invoice->invoice_no, false)
            ->assertSee('Overdue', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('900.00', false)
            ->assertSee('From invoices on your tenancy', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee($invoice->invoice_no, false)
            ->assertSee('900.00', false)
            ->assertSee('Overdue', false);
    }

    public function test_recurring_rent_generation_is_idempotent_per_period(): void
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
                'issue_date' => '2026-09-01',
                'due_date' => '2026-09-08',
                'period_start' => '2026-09-01',
                'period_end' => '2026-09-30',
                'auto_recurring' => '1',
            ])
            ->assertRedirect();

        $tenancy->refresh();
        $this->assertTrue((bool) $tenancy->rent_auto_invoice);
        $this->assertSame('2026-10-01', $tenancy->rent_next_period_start?->toDateString());

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-10-01'])
            ->assertSuccessful();

        $this->assertSame(2, RentInvoice::query()->forAccount($accountId)->count());
        $october = RentInvoice::query()
            ->forAccount($accountId)
            ->whereDate('period_start', '2026-10-01')
            ->first();
        $this->assertNotNull($october);
        $this->assertEquals(1200.0, (float) $october->amount);
        $this->assertSame('2026-10-08', $october->due_date?->toDateString());

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-10-01'])
            ->assertSuccessful();

        $this->assertSame(2, RentInvoice::query()->forAccount($accountId)->count());

        $this->assertSame(2, NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceIssued->value)
            ->where('notifiable_id', $tenant->id)
            ->distinct()
            ->count('subject_id'));
    }

    public function test_weekly_recurring_stays_weekly_and_unknown_frequency_creates_nothing(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);

        $tenancy->forceFill([
            'frequency' => 'Weekly',
            'rent' => 300,
            'rent_due_day' => 8,
            'rent_auto_invoice' => true,
            'rent_auto_invoice_tenant_user_id' => $tenant->id,
            'rent_next_period_start' => '2026-09-01',
        ])->save();

        $this->artisan('rent-invoices:generate-recurring', ['--as-of' => '2026-09-01'])
            ->assertSuccessful();

        $weekly = RentInvoice::query()->forAccount($accountId)->firstOrFail();
        $this->assertSame('2026-09-01', $weekly->due_date?->toDateString());
        $this->assertSame('2026-09-07', $weekly->period_end?->toDateString());
        $tenancy->refresh();
        $this->assertSame('2026-09-08', $tenancy->rent_next_period_start?->toDateString());

        $service = app(\App\Services\Finance\RentFinanceService::class);
        $canonical = new \ReflectionMethod($service, 'canonicalFrequency');
        $generate = new \ReflectionMethod($service, 'generateRecurringForTenancy');
        $this->assertNull($canonical->invoke($service, 'quarterly'));

        $tenancy->frequency = 'quarterly';
        $this->assertSame(0, $generate->invoke($service, $tenancy, \Carbon\Carbon::parse('2026-10-01')));
        $this->assertSame(1, RentInvoice::query()->forAccount($accountId)->count());
        $tenancy->refresh();
        $this->assertSame('Weekly', $tenancy->frequency);
        $this->assertSame('2026-09-08', $tenancy->rent_next_period_start?->toDateString());
    }

    public function test_household_sees_the_bill_and_notices_follow_issue_pay_and_void(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$billed] = $this->createTenantOnAccount($accountId);
        [$housemate] = $this->createTenantOnAccount($accountId);
        $billed->forceFill(['name' => 'Ada Tenant'])->save();
        $housemate->forceFill(['name' => 'Ben Housemate'])->save();
        [, $tenancy] = $this->createLet($accountId, $landlord, $billed);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $housemate->id,
            'is_main_person' => false,
            'can_login' => true,
        ]);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $billed->id,
                'amount' => 800,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertRedirect();

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $issued = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceIssued->value)
            ->where('subject_id', $invoice->id)
            ->get();
        $this->assertTrue($issued->contains(fn ($log) => (int) $log->notifiable_id === $billed->id));
        $this->assertTrue($issued->contains(fn ($log) => (int) $log->notifiable_id === $housemate->id));
        $this->assertFalse($issued->contains(fn ($log) => (int) $log->notifiable_id === $landlord->id));
        $tenantNotice = $issued->first(fn ($log) => (int) $log->notifiable_id === $housemate->id);
        $this->assertSame(route('tenant.rent.show', $invoice), $tenantNotice->payload['action_url'] ?? null);

        $this->actingAs($housemate)->withSession($session)
            ->get(route('tenant.rent.show', $invoice))
            ->assertOk()
            ->assertSee('Billed to', false)
            ->assertSee('Ada Tenant', false)
            ->assertSee('800.00', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 800,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
                'reference' => $invoice->invoice_no,
            ])
            ->assertRedirect();

        $paid = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinancePaymentReceived->value)
            ->where('subject_id', $invoice->id)
            ->get();
        $this->assertTrue($paid->contains(fn ($log) => (int) $log->notifiable_id === $billed->id));
        $this->assertTrue($paid->contains(fn ($log) => (int) $log->notifiable_id === $landlord->id));
        $landlordNotice = $paid->first(fn ($log) => (int) $log->notifiable_id === $landlord->id);
        $this->assertSame(route('admin.finance.show', $invoice), $landlordNotice->payload['action_url'] ?? null);

        $this->actingAs($housemate)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Paid', false)
            ->assertSee('0.00', false);
    }

    public function test_void_notice_removes_the_bill_from_what_the_tenant_owes(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 450,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.void', $invoice))
            ->assertRedirect();

        $this->assertTrue(NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceVoided->value)
            ->where('subject_id', $invoice->id)
            ->where('notifiable_id', $tenant->id)
            ->exists());

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Your landlord has not sent a rent invoice yet', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('0.00', false)
            ->assertSee('No rent invoices yet', false);
    }

    public function test_rent_due_and_overdue_reminders_use_rent_invoices(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $at = now()->timezone('Europe/London');

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenant->id,
                'amount' => 640,
                'issue_date' => $at->toDateString(),
                'due_date' => $at->copy()->addDays(3)->toDateString(),
            ]);

        $due = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $this->artisan('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => $at->format('Y-m-d H:i:s'),
        ])->assertSuccessful();

        $dueLogs = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceDue->value)
            ->where('subject_id', $due->id)
            ->get();
        $this->assertTrue($dueLogs->contains(fn ($log) => (int) $log->notifiable_id === $tenant->id && ($log->payload['milestone'] ?? null) === 'due-3'));
        $this->assertTrue($dueLogs->contains(fn ($log) => (int) $log->notifiable_id === $landlord->id));

        $due->forceFill(['due_date' => $at->copy()->subDay()->toDateString()])->save();

        $this->artisan('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => $at->format('Y-m-d H:i:s'),
        ])->assertSuccessful();

        $overdueCount = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceOverdue->value)
            ->where('subject_id', $due->id)
            ->count();
        $this->assertGreaterThan(0, $overdueCount);

        $this->artisan('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => $at->format('Y-m-d H:i:s'),
        ])->assertSuccessful();

        $this->assertSame($overdueCount, NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceOverdue->value)
            ->where('subject_id', $due->id)
            ->count());
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
            'frequency' => 'Monthly',
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
