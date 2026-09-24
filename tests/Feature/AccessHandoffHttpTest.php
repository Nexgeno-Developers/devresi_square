<?php

namespace Tests\Feature;

use App\Models\BankDetails;
use App\Models\Document;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RentInvoice;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Finance\RentFinanceService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessHandoffHttpTest extends TestCase
{
    public function test_another_tenants_invoice_document_and_repair_stay_closed(): void
    {
        [$landlordA, $accountA, $tenantA] = $this->createLandlord('a');
        [$propertyA, $tenancyA] = $this->attachLet($accountA, $landlordA, $tenantA, '1 Alpha Road', 'A1 1AA');
        $invoiceA = $this->invoice($accountA, $tenancyA, $tenantA);
        $documentA = $this->sharedDocument($accountA, $propertyA, 'Alpha gas');
        $repairA = $this->repair($accountA, $propertyA, $tenantA, 'Alpha leak');

        $tenantB = $this->tenantOn($accountA, 'b-same');
        [$propertyB] = $this->attachLet($accountA, $landlordA, $tenantB, '2 Beta Road', 'B2 2BB');
        $invoiceB = $this->invoice($accountA, Tenancy::query()->where('property_id', $propertyB->id)->first(), $tenantB);
        $documentB = $this->sharedDocument($accountA, $propertyB, 'Beta gas');
        $repairB = $this->repair($accountA, $propertyB, $tenantB, 'Beta leak');

        $sessionA = ['current_account_id' => $accountA];
        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('tenant.rent.show', $invoiceB)));
        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('tenant.documents.download', $documentB)));
        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('admin.property_repairs.show', ['id' => $repairB->id])));

        $this->actingAs($tenantA)->withSession($sessionA)
            ->get(route('tenant.rent.show', $invoiceA))
            ->assertOk();
        $this->actingAs($tenantA)->withSession($sessionA)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('Alpha leak', false)
            ->assertDontSee('Beta leak', false);

        [$landlordC, $accountC, $tenantC] = $this->createLandlord('c');
        [$propertyC, $tenancyC] = $this->attachLet($accountC, $landlordC, $tenantC, '3 Gamma Road', 'C3 3CC');
        $invoiceC = $this->invoice($accountC, $tenancyC, $tenantC);
        $documentC = $this->sharedDocument($accountC, $propertyC, 'Gamma gas');
        $repairC = $this->repair($accountC, $propertyC, $tenantC, 'Gamma leak');

        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('tenant.rent.show', $invoiceC)));
        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('tenant.documents.download', $documentC)));
        $this->assertClosed($this->actingAs($tenantA)->withSession($sessionA)->get(route('admin.property_repairs.show', ['id' => $repairC->id])));
    }

    public function test_soft_deleted_home_drops_off_the_portal(): void
    {
        [$landlord, $accountId, $tenant] = $this->createLandlord('gone');
        [$property, $tenancy] = $this->attachLet($accountId, $landlord, $tenant, '9 Gone Road', 'G9 9GG');
        $invoice = $this->invoice($accountId, $tenancy, $tenant);
        $this->repair($accountId, $property, $tenant, 'Gone leak');
        $session = ['current_account_id' => $accountId];

        $property->delete();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('9 Gone Road', false)
            ->assertDontSee('data-address-group="9 Gone Road"', false)
            ->assertDontSee('Active tenancy', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Your landlord has not sent a rent invoice yet.', false)
            ->assertDontSee('data-address-group="9 Gone Road"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('No open repairs.', false)
            ->assertDontSee('data-repair-home="9 Gone Road"', false);
    }

    public function test_suspended_account_blocks_card_pay_and_keeps_bank_details_and_billing(): void
    {
        [$landlord, $accountId, $tenant] = $this->createLandlord('pay');
        [$property, $tenancy] = $this->attachLet($accountId, $landlord, $tenant, '4 Pay Road', 'P4 4PP');
        $invoice = $this->invoice($accountId, $tenancy, $tenant);
        BankDetails::create([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'account_name' => 'Pay Landlord',
            'account_no' => '11223344',
            'sort_code' => '11-22-33',
            'bank_name' => 'Pause Bank',
            'is_active' => true,
            'is_primary' => true,
        ]);

        DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('data-card-paused="1"', false)
            ->assertSee('11223344', false)
            ->assertSee('Pause Bank', false)
            ->assertDontSee('tenant/rent/'.$invoice->id.'/pay', false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertRedirect(route('tenant.rent'));

        $this->assertSame('issued', $invoice->fresh()->status);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.billing.index'))
            ->assertOk();

        DB::table('accounts')->where('id', $accountId)->update(['status' => 'cancelled']);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.rent.pay', $invoice))
            ->assertRedirect(route('tenant.rent'));
        $this->assertSame('issued', $invoice->fresh()->status);
    }

    public function test_two_lets_are_grouped_by_address_and_one_payment_stays_on_its_invoice(): void
    {
        [$landlord, $accountId, $tenant] = $this->createLandlord('two');
        [$propertyA, $tenancyA] = $this->attachLet($accountId, $landlord, $tenant, '10 First Road', 'F1 1FF');
        [$propertyB, $tenancyB] = $this->attachLet($accountId, $landlord, $tenant, '20 Second Road', 'S2 2SS');
        $invoiceA = $this->invoice($accountId, $tenancyA, $tenant, 800);
        $invoiceB = $this->invoice($accountId, $tenancyB, $tenant, 900);
        $this->repair($accountId, $propertyA, $tenant, 'First leak');
        $this->repair($accountId, $propertyB, $tenant, 'Second leak');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('data-address-group="10 First Road"', false)
            ->assertSee('data-address-group="20 Second Road"', false)
            ->assertSee('data-rent-home="10 First Road"', false)
            ->assertSee('data-rent-home="20 Second Road"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('data-address-group="10 First Road"', false)
            ->assertSee('data-address-group="20 Second Road"', false)
            ->assertSee('data-invoice-id="'.$invoiceA->id.'"', false)
            ->assertSee('data-invoice-id="'.$invoiceB->id.'"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('data-repair-home="10 First Road"', false)
            ->assertSee('data-repair-home="20 Second Road"', false)
            ->assertSee('First leak', false)
            ->assertSee('Second leak', false);

        app(RentFinanceService::class)->recordPayment($invoiceA, [
            'amount' => 800,
            'paid_at' => now()->toDateString(),
            'method' => 'bank',
            'reference' => 'first-only',
        ], $landlord->id);

        $this->assertSame('paid', $invoiceA->fresh()->status);
        $this->assertSame('issued', $invoiceB->fresh()->status);
        $this->assertEquals(900.0, (float) $invoiceB->fresh()->balance);
    }

    public function test_revoke_blocks_the_next_request_and_resend_does_not_restore_access(): void
    {
        [$landlord, $accountId, $tenant] = $this->createLandlord('revoke');
        $this->attachLet($accountId, $landlord, $tenant, '5 Revoke Road', 'R5 5RR');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.portal-access.revoke', $tenant))
            ->assertRedirect();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertRedirect(route('login'));

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.portal-access.resend', $tenant))
            ->assertNotFound();

        $this->assertDatabaseHas('account_users', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'status' => 'disabled',
            'can_login' => 0,
        ]);
    }

    public function test_landlord_without_a_role_can_open_the_workspace_and_an_estate_agent_cannot_open_agency_books(): void
    {
        [$landlord, $accountId] = $this->createLandlord('role');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)->get(route('admin.tenancies.all'))->assertOk();
        $this->actingAs($landlord)->withSession($session)->get(route('admin.property_repairs.index'))->assertOk();
        $this->actingAs($landlord)->withSession($session)->get(route('admin.compliance.index'))->assertOk();
        $this->actingAs($landlord)->withSession($session)->get(route('admin.finance.index'))->assertOk();

        $agent = User::create([
            'name' => 'Estate Agent On Landlord',
            'email' => 'estate-on-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $agent->assignRole(Role::findOrCreate('Estate Agent', 'web'));
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $agent->id,
            'member_type' => 'staff',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($agent)->withSession($session)
            ->get(route('backend.accounting.masters.banks.index'))
            ->assertForbidden();
        $this->actingAs($agent)->withSession($session)
            ->get(route('admin.offers.index'))
            ->assertForbidden();
    }

    private function assertClosed($response): void
    {
        $this->assertContains($response->status(), [403, 404]);
    }

    private function invoice(int $accountId, Tenancy $tenancy, User $tenant, float $amount = 500): RentInvoice
    {
        return app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => $amount,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    private function sharedDocument(int $accountId, Property $property, string $title): Document
    {
        return Document::create([
            'account_id' => $accountId,
            'documentable_id' => $property->id,
            'documentable_type' => $property->getMorphClass(),
            'title' => $title,
            'visibility' => 'portal',
        ]);
    }

    private function repair(int $accountId, Property $property, User $tenant, string $description): RepairIssue
    {
        $category = RepairCategory::query()->orderBy('id')->first()
            ?: RepairCategory::create([
                'name' => 'Access '.uniqid(),
                'parent_id' => null,
                'level' => 1,
                'description' => 'General',
                'status' => 1,
                'position' => 0,
            ]);

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
            'description' => $description,
            'status' => 'Open',
            'priority' => 'medium',
            'reference_number' => 'ACC-'.uniqid(),
            'created_by' => $tenant->id,
        ]);
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function attachLet(int $accountId, User $landlord, User $tenant, string $line, string $postcode): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => $line,
            'city' => 'London',
            'postcode' => $postcode,
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'deposit' => 0,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'pending',
        ]);

        return [$property, $tenancy];
    }

    /**
     * @return array{0: User, 1: int, 2: User}
     */
    private function createLandlord(string $suffix): array
    {
        $landlord = User::create([
            'name' => 'Access Landlord '.$suffix,
            'email' => 'access-landlord-'.$suffix.'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = $this->tenantOn($accountId, $suffix);

        return [$landlord, $accountId, $tenant];
    }

    private function tenantOn(int $accountId, string $suffix): User
    {
        $tenant = User::create([
            'name' => 'Access Tenant '.$suffix,
            'first_name' => 'Access',
            'email' => 'access-tenant-'.$suffix.'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole(Role::findOrCreate('Tenant', 'web'));

        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tenant;
    }
}
