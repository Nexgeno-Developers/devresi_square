<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantTenancyAuthorizationTest extends TestCase
{
    public function test_tenant_cannot_open_staff_tenancy_pages(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.all'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.create'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.show', ['id' => 1]))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.saas.accounts.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.billing.index'))
            ->assertForbidden();
    }

    public function test_landlord_cannot_view_another_accounts_tenancy(): void
    {
        [$userA, $accountA] = $this->createPortalUser('Landlord', 'owner');
        [, $accountB] = $this->createPortalUser('Landlord', 'owner');

        $propertyId = \DB::table('properties')->insertGetId([
            'account_id' => $accountB,
            'line_1' => '1 Isolation Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenancyId = \DB::table('tenancies')->insertGetId([
            'account_id' => $accountB,
            'property_id' => $propertyId,
            'status' => 'Active',
            'move_in' => now()->toDateString(),
            'rent' => 1000,
            'deposit' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($userA)
            ->withSession(['current_account_id' => $accountA])
            ->get(route('admin.tenancies.show', ['id' => $tenancyId]))
            ->assertForbidden();
    }

    public function test_landlord_can_open_the_tenancy_list(): void
    {
        [$user, $accountId] = $this->createPortalUser('Landlord', 'owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.tenancies.all'))
            ->assertOk();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPortalUser(string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' Tenancy Auth',
            'email' => 'tenancy-auth-'.uniqid().'@resisquare.test',
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
            'member_type' => $memberType,
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }
}
