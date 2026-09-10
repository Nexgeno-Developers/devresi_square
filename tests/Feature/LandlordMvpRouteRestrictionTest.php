<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordMvpRouteRestrictionTest extends TestCase
{
    public function test_landlord_cannot_open_accounting_or_sales_offer(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.accounting.masters.banks.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.offers.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('cache.clear'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('customer.statements'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('user-categories.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.saas.plans.index'))
            ->assertForbidden();
    }

    public function test_landlord_can_open_dashboard_and_properties(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.properties.index'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.billing.index'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.portal-access.index'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.owner-groups.index'))
            ->assertOk()
            ->assertSee('Add New Owner Group', false)
            ->assertDontSee('backend.owner_groups', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.owner-groups.create'))
            ->assertOk()
            ->assertSee('Select a property', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Contacts', false)
            ->assertSee('No contacts yet', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.users.create'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee('Documents', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Finance', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.create'))
            ->assertOk()
            ->assertSee('Add tenancy', false)
            ->assertSee('Search menu', false)
            ->assertSee('asset/js/select2.min.js', false)
            ->assertSee('Quick add tenant', false)
            ->assertDontSee('Property Manager', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.events.calendar'))
            ->assertOk()
            ->assertSee('Add event', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.property_repairs.create'))
            ->assertOk()
            ->assertSee('Which property needs the repair?', false);
    }

    public function test_landlord_dashboard_hides_agency_chrome(): void
    {
        [$user, $accountId] = $this->createLandlord();

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('Tenancies', false)
            ->assertSee('Documents', false)
            ->assertSee('Repair', false)
            ->assertSee('Portal Access', false)
            ->assertSee('Owner Groups', false)
            ->assertSee('Contacts', false)
            ->assertSee('Finance', false)
            ->assertDontSee('Sales Offer', false)
            ->assertDontSee('Clear Cache', false)
            ->assertDontSee('>My Statement<', false)
            ->assertDontSee('> Accounting', false)
            ->assertDontSee('Work orders', false)
            ->assertDontSee('Property managers', false)
            ->assertDontSee('Issue List (Tabbed)', false)
            ->assertDontSee('Invoice Received', false)
            ->assertDontSee('Invoice Paid', false);
    }

    public function test_landlord_property_and_contact_screens_hide_agency_tabs(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $property = \App\Models\Property::create([
            'account_id' => $accountId,
            'created_by' => $user->id,
            'line_1' => '12 Client Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'property_type' => 'lettings',
        ]);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'Property',
            ]))
            ->assertOk()
            ->assertSee('Owners', false)
            ->assertSee('Tenancy', false)
            ->assertDontSee('value="sales"', false)
            ->assertDontSee('Offers tab', false)
            ->assertDontSee('Brochure', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'Offers',
            ]))
            ->assertRedirect(route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'Property',
            ]));

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Contacts', false)
            ->assertDontSee('data-tab-name="letters"', false)
            ->assertDontSee('data-tab-name="statement"', false)
            ->assertDontSee('data-tab-name="appointments"', false)
            ->assertDontSee('data-tab-name="bank"', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.property_repairs.index'))
            ->assertOk()
            ->assertDontSee('Invoice Received', false)
            ->assertDontSee('Invoice Paid', false);
    }

    public function test_tenant_statement_url_redirects_to_portal_rent(): void
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Tenant Statement',
            'email' => 'tenant-statement-'.uniqid().'@resisquare.test',
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
            'member_type' => 'tenant',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('customer.statements'))
            ->assertRedirect(route('tenant.rent'));
    }

    public function test_tenant_header_does_not_offer_statement_or_cache(): void
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Tenant Chrome',
            'first_name' => 'Tina',
            'email' => 'tenant-chrome-'.uniqid().'@resisquare.test',
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
            'member_type' => 'tenant',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('My Statement', false)
            ->assertDontSee('Clear Cache', false)
            ->assertDontSee('Sales Offer', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Landlord Mvp',
            'email' => 'landlord-mvp-'.uniqid().'@resisquare.test',
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
}
