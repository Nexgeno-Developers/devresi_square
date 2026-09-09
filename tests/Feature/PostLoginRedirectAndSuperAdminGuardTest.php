<?php

namespace Tests\Feature;

use App\Models\AccountUser;
use App\Models\User;
use App\Support\PostLoginRedirect;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostLoginRedirectAndSuperAdminGuardTest extends TestCase
{
    public function test_login_sends_landlord_to_dashboard_and_tenant_to_home(): void
    {
        [$landlord, $accountId] = $this->createLandlordOwner();

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId]);
        $this->assertSame('backend.dashboard', PostLoginRedirect::routeName($landlord));

        $tenant = User::create([
            'name' => 'Tenant Redirect',
            'email' => 'tenant-redirect-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        Role::findOrCreate('Tenant', 'web');
        $tenant->assignRole('Tenant');

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId]);
        $this->assertSame('backend.home', PostLoginRedirect::routeName($tenant));
    }

    public function test_login_sends_super_admin_to_saas_accounts(): void
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $user = User::create([
            'name' => 'Super Admin Redirect',
            'email' => 'super-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $this->assertSame('backend.saas.accounts.index', PostLoginRedirect::routeName($user));
    }

    public function test_super_admin_cannot_be_added_to_account_users(): void
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $user = User::create([
            'name' => 'Super Admin Guard',
            'email' => 'super-admin-guard-'.uniqid().'@resisquare.test',
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

        $this->expectException(RuntimeException::class);

        AccountUser::create([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => true,
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlordOwner(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Landlord Redirect',
            'email' => 'landlord-redirect-'.uniqid().'@resisquare.test',
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
