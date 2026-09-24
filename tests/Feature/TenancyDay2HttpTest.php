<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 19: day-2 tenancy create/edit/show + occupancy sync.
 */
class TenancyDay2HttpTest extends TestCase
{
    public function test_store_active_tenancy_sets_let_agreed_and_show_has_layout(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->seedLandlordPropertyAndTenant();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.store'), [
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => now()->toDateString(),
                'move_out' => now()->addYear()->toDateString(),
                'rent' => 1250,
                'deposit' => 1250,
                'deposit_number' => '5',
                'term_months' => 12,
                'term_days' => 0,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect(route('admin.tenancies.all'));

        $tenancy = Tenancy::query()->where('property_id', $property->id)->where('status', 'Active')->first();
        $this->assertNotNull($tenancy);
        $this->assertSame(1250.0, (float) $tenancy->rent);

        $property->refresh();
        $this->assertSame('let agreed', $property->letting_current_status);

        $show = $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id));

        $show->assertOk();
        $show->assertSee('data-tenancy-show="1"', false);
        $show->assertSee('Edit tenancy', false);
        $show->assertSee('£1,250.00', false);
        $show->assertSee($tenant->name, false);
    }

    public function test_update_persists_rent_and_preserves_member_confirmation(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->seedLandlordPropertyAndTenant();
        $session = ['current_account_id' => $accountId];

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
            'deposit' => 900,
            'move_in' => now()->toDateString(),
        ]);

        $member = TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => 1,
            'details_status' => 'confirmed',
            'details_confirmed_at' => now(),
            'right_to_rent_required' => 1,
        ]);

        $property->forceFill(['letting_current_status' => 'available'])->save();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.update', $tenancy->id), [
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => now()->toDateString(),
                'move_out' => now()->addMonths(6)->toDateString(),
                'rent' => 1100,
                'deposit' => 1100,
                'deposit_number' => '5',
                'term_months' => 6,
                'term_days' => 0,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect(route('admin.tenancies.show', $tenancy->id));

        $tenancy->refresh();
        $this->assertSame(1100.0, (float) $tenancy->rent);

        $member->refresh();
        $this->assertSame('confirmed', $member->details_status);
        $this->assertTrue((bool) $member->right_to_rent_required);

        $property->refresh();
        $this->assertSame('let agreed', $property->letting_current_status);
    }

    public function test_archiving_last_active_tenancy_marks_property_available(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->seedLandlordPropertyAndTenant();
        $session = ['current_account_id' => $accountId];

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'deposit' => 1000,
            'move_in' => now()->toDateString(),
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => 1,
        ]);

        $property->forceFill(['letting_current_status' => 'let agreed'])->save();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.update', $tenancy->id), [
                'property_id' => $property->id,
                'status' => 'Archived',
                'move_in' => now()->toDateString(),
                'rent' => 1000,
                'deposit' => 1000,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect();

        $property->refresh();
        $this->assertSame('available', $property->letting_current_status);
    }

    /**
     * @return array{0: User, 1: int, 2: Property, 3: User}
     */
    private function seedLandlordPropertyAndTenant(): array
    {
        foreach (['create tenancies', 'edit tenancies', 'delete tenancies', 'view tenancies'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create tenancies', 'edit tenancies', 'delete tenancies', 'view tenancies']);

        $landlord = User::create([
            'name' => 'Day2 Landlord '.uniqid(),
            'email' => 'day2-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $landlord->assignRole($role);

        $tenantRole = Role::findOrCreate('Tenant', 'web');
        $tenant = User::create([
            'name' => 'Day2 Tenant '.uniqid(),
            'email' => 'day2-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole($tenantRole);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$landlord, $tenant] as $user) {
            DB::table('account_users')->insert([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'member_type' => $user->id === $landlord->id ? 'owner' : 'tenant',
                'access_level' => 'full',
                'can_login' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '22 Day Two Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
            'letting_current_status' => 'available',
        ]);

        return [$landlord, $accountId, $property, $tenant];
    }
}
