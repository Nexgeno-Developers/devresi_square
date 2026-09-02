<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantPortalHttpTest extends TestCase
{
    public function test_tenant_home_shows_portal_dashboard(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Tenant portal', false)
            ->assertSee('Outstanding rent', false);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.tenancy'))
            ->assertOk()
            ->assertSee('Your lease', false);
    }

    public function test_landlord_cannot_open_tenant_tenancy_page(): void
    {
        [$user, $accountId] = $this->createPortalUser('Landlord', 'owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.tenancy'))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPortalUser(string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' Portal',
            'first_name' => $roleName,
            'email' => 'tenant-portal-'.uniqid().'@resisquare.test',
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
