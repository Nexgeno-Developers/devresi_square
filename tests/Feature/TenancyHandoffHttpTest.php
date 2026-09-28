<?php

namespace Tests\Feature;

use App\Models\BankDetails;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Finance\RentFinanceService;
use App\Services\Portal\TenantPortalService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenancyHandoffHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['crm_notifications.enabled' => false]);
    }

    public function test_invite_stays_hidden_until_a_property_is_linked(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => null,
            'status' => 'Active',
            'rent' => 900,
            'deposit' => 900,
            'frequency' => 'Monthly',
        ]);

        $page = $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.people.index'));

        $page->assertOk()
            ->assertSee('data-orphan-tenancy="1"', false)
            ->assertSee('not linked to a property', false)
            ->assertSee('Edit tenancy', false)
            ->assertSee(route('admin.tenancies.edit', $tenancy->id), false)
            ->assertDontSee('Property #', false)
            ->assertDontSee('name="tenancy_id"', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.portal-access.invite'), [
                'name' => 'Orphan Tenant',
                'email' => 'orphan-'.uniqid().'@resisquare.test',
                'tenancy_id' => $tenancy->id,
            ])
            ->assertSessionHasErrors('tenancy_id');

        $message = session('errors')->get('tenancy_id')[0] ?? '';
        $this->assertStringNotContainsString((string) $tenancy->id, $message);
        $this->assertStringContainsString('property', strtolower($message));
    }

    public function test_rent_facts_match_on_landlord_tenant_and_the_next_invoice(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet([
            'move_in' => '2026-09-01',
            'rent' => 1250,
            'frequency' => 'Monthly',
            'rent_due_day' => 5,
            'deposit' => 0,
        ]);
        $session = ['current_account_id' => $accountId];

        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1250,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-05',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
        ]);

        $this->assertSame(5, (int) $invoice->due_date->day);

        $facts = ['1 Sep 2026', '£1,250.00', 'Monthly', 'Day 5 of each month', '5 Sep 2026'];

        $landlordPage = $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id));
        $landlordPage->assertOk()->assertSee('data-let-facts="landlord"', false);
        foreach (array_slice($facts, 0, 4) as $fact) {
            $landlordPage->assertSee($fact, false);
        }

        $tenantPage = $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.tenancy'));
        $tenantPage->assertOk()->assertSee('data-let-facts="tenant"', false);
        foreach (array_slice($facts, 0, 4) as $fact) {
            $tenantPage->assertSee($fact, false);
        }

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Due 5 Sep 2026', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.rent-ledger', $tenancy->id))
            ->assertOk()
            ->assertSee('5 Sep 2026', false);
    }

    public function test_archiving_a_tenancy_closes_that_portal_and_stops_new_rent(): void
    {
        [$landlord, $accountId, $tenant, $tenancy, $property] = $this->createLet([
            'rent_auto_invoice' => true,
            'rent_next_period_start' => now()->subDay()->toDateString(),
            'frequency' => 'Monthly',
            'rent_due_day' => 1,
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        $session = ['current_account_id' => $accountId];

        $otherProperty = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '2 Kept Street',
            'city' => 'London',
            'postcode' => 'E2 2BB',
        ]);
        $other = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $otherProperty->id,
            'status' => 'Active',
            'rent' => 800,
            'deposit' => 0,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $other->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        $category = RepairCategory::create([
            'name' => 'Handoff '.uniqid(),
            'parent_id' => null,
            'level' => 1,
            'description' => 'Kitchen',
            'status' => 1,
            'position' => 0,
        ]);
        RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
            'description' => 'Leak on the ended let',
            'status' => 'Pending',
            'reference_number' => 'END-'.uniqid(),
            'created_by' => $landlord->id,
        ]);

        app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1250,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
        ]);

        $portal = app(TenantPortalService::class);
        $this->assertCount(2, $portal->tenanciesFor($tenant, $accountId));

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.update', $tenancy->id), [
                'property_id' => $property->id,
                'status' => 'Archived',
                'move_in' => now()->subMonth()->toDateString(),
                'rent' => 1250,
                'deposit' => 0,
                'frequency' => 'Monthly',
                'rent_due_day' => 1,
                'term_months' => 12,
                'term_days' => 0,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect();

        $this->assertSame('Archived', $tenancy->fresh()->status);
        $this->assertDatabaseHas('account_users', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'status' => 'active',
            'can_login' => 1,
        ]);

        $open = $portal->tenanciesFor($tenant, $accountId);
        $this->assertCount(1, $open);
        $this->assertSame($other->id, $open->first()->id);
        $this->assertTrue($portal->invoicesFor($tenant, $accountId, $open)->where('tenancy_id', $tenancy->id)->isEmpty());
        $this->assertTrue($portal->repairsFor($tenant, $accountId, $open)->where('property_id', $property->id)->isEmpty());

        $before = RentInvoice::query()->where('tenancy_id', $tenancy->id)->count();
        app(RentFinanceService::class)->generateDueRecurring(now());
        $this->assertSame($before, RentInvoice::query()->where('tenancy_id', $tenancy->id)->count());

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.tenancy'))
            ->assertOk()
            ->assertSee('2 Kept Street', false)
            ->assertDontSee('1 Handoff Street', false);
    }

    public function test_archiving_the_property_revokes_the_portal_login(): void
    {
        [$landlord, $accountId, $tenant, $tenancy, $property] = $this->createLet([
            'rent_auto_invoice' => true,
            'rent_next_period_start' => now()->subDay()->toDateString(),
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.properties.delete', $property->id))
            ->assertOk();

        $this->assertSame('Archived', $tenancy->fresh()->status);
        $this->assertDatabaseHas('account_users', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'status' => 'disabled',
            'can_login' => 0,
        ]);

        $portal = app(TenantPortalService::class);
        $this->assertTrue($portal->tenanciesFor($tenant, $accountId)->isEmpty());

        $before = RentInvoice::query()->where('tenancy_id', $tenancy->id)->count();
        app(RentFinanceService::class)->generateDueRecurring(now());
        $this->assertSame($before, RentInvoice::query()->where('tenancy_id', $tenancy->id)->count());

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertRedirect(route('login'));
    }

    public function test_right_to_rent_stays_with_the_landlord(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('Right to rent follow-up register', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.tenancy'))
            ->assertOk()
            ->assertDontSee('Right to rent', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('Right to rent', false);
    }

    public function test_incomplete_deposit_shows_for_the_landlord_and_the_tenant_sees_only_the_scheme(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet([
            'deposit' => 1500,
            'deposit_held_by' => 'Landlord client account',
        ]);
        $session = ['current_account_id' => $accountId];

        BankDetails::create([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'account_name' => 'Landlord rent account',
            'account_no' => '99887766',
            'sort_code' => '11-22-33',
            'bank_name' => 'Test Bank',
            'is_active' => true,
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('deposit needs you', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('data-deposit-protection="1"', false)
            ->assertSee('Needs you', false);

        $tenantPage = $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.tenancy'));
        $tenantPage->assertOk()
            ->assertSee('Your landlord has not recorded the protection scheme yet', false)
            ->assertDontSee('99887766', false)
            ->assertDontSee('11-22-33', false)
            ->assertDontSee('Landlord client account', false);

        $tenancy->forceFill([
            'deposit_scheme' => 'tds',
            'deposit_protected_at' => '2026-09-10 09:00:00',
        ])->save();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.tenancy'))
            ->assertOk()
            ->assertSee('Tenancy Deposit Scheme', false)
            ->assertSee('Protection recorded 10 Sep 2026', false)
            ->assertDontSee('99887766', false)
            ->assertDontSee('Landlord client account', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: int, 2: User, 3: Tenancy, 4: Property}
     */
    private function createLet(array $overrides = []): array
    {
        [$landlord, $accountId] = $this->createLandlord();
        $role = Role::findOrCreate('Tenant', 'web');
        $tenant = User::create([
            'name' => 'Handoff Tenant',
            'first_name' => 'Handoff',
            'email' => 'handoff-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole($role);

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

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '1 Handoff Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);

        $tenancy = Tenancy::create(array_merge([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1250,
            'deposit' => 0,
            'frequency' => 'Monthly',
            'rent_due_day' => 5,
            'move_in' => '2026-09-01',
        ], $overrides));

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'confirmed',
        ]);

        return [$landlord, $accountId, $tenant, $tenancy, $property];
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Handoff Landlord',
            'email' => 'handoff-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
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
}
