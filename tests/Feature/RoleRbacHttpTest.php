<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PlatformRoles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 10: RBAC — role mutations gated; platform roles immutable.
 */
class RoleRbacHttpTest extends TestCase
{
    public function test_landlord_cannot_open_or_mutate_roles(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('roles.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->post(route('roles.store'), [
                'name' => 'Hacker Role '.uniqid(),
                'permissions' => [],
            ])
            ->assertForbidden();
    }

    public function test_user_without_role_permission_cannot_store_roles(): void
    {
        [$user, $accountId] = $this->createEstateAgentWithoutRolePerms();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->post(route('roles.store'), [
                'name' => 'Unauthorized Role '.uniqid(),
                'permissions' => [Permission::findOrCreate('view contacts', 'web')->id],
            ])
            ->assertForbidden();
    }

    public function test_platform_roles_cannot_be_edited_or_deleted(): void
    {
        [$user, $accountId] = $this->createEstateAgentWithRolePerms();
        $session = ['current_account_id' => $accountId];

        $super = Role::findOrCreate('Super Admin', 'web');

        $this->actingAs($user)->withSession($session)
            ->get(route('roles.edit', $super->id))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->put(route('roles.update', $super->id), [
                'name' => 'Super Admin Hijacked',
                'permissions' => [Permission::findOrCreate('view contacts', 'web')->id],
            ])
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('roles.destroy', $super->id))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', [
            'id' => $super->id,
            'name' => 'Super Admin',
        ]);
    }

    public function test_cannot_create_a_role_with_a_platform_name(): void
    {
        [$user, $accountId] = $this->createEstateAgentWithRolePerms();

        $this->actingAs($user)->withSession(['current_account_id' => $accountId])
            ->post(route('roles.store'), [
                'name' => 'Landlord',
                'permissions' => [Permission::findOrCreate('view contacts', 'web')->id],
            ])
            ->assertForbidden();

        $this->assertTrue(PlatformRoles::isProtected('Landlord'));
    }

    public function test_only_super_admin_can_add_global_permissions(): void
    {
        [$agent, $accountId] = $this->createEstateAgentWithRolePerms();

        $this->actingAs($agent)->withSession(['current_account_id' => $accountId])
            ->post(route('roles.permission'), [
                'name' => 'invented permission '.uniqid(),
            ])
            ->assertForbidden();

        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));

        $permName = 'launch-step-10-perm-'.uniqid();

        $this->actingAs($admin)
            ->post(route('roles.permission'), ['name' => $permName])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('permissions', ['name' => $permName]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        return $this->createAccountUser('Landlord', 'landlord', 'owner');
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createEstateAgentWithoutRolePerms(): array
    {
        return $this->createAccountUser('Estate Agent', 'estate_agent_company', 'owner');
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createEstateAgentWithRolePerms(): array
    {
        [$user, $accountId] = $this->createAccountUser('Estate Agent', 'estate_agent_company', 'owner');

        foreach (['view staff roles', 'add staff role', 'edit staff role', 'delete staff role'] as $perm) {
            $permission = Permission::findOrCreate($perm, 'web');
            $user->givePermissionTo($permission);
        }

        $this->attachRolesPermissionsPlan($accountId);

        return [$user, $accountId];
    }

    private function attachRolesPermissionsPlan(int $accountId): void
    {
        $planId = \DB::table('plans')->where('code', 'estate_agent_company')->value('id');

        if (! $planId) {
            $planId = \DB::table('plans')->insertGetId([
                'code' => 'estate_agent_company',
                'name' => 'Estate Agent Company',
                'target_account_type' => 'estate_agent_company',
                'monthly_price_minor' => 14900,
                'annual_price_minor' => 149000,
                'currency' => 'GBP',
                'trial_days' => 7,
                'property_limit' => 50,
                'branch_limit' => 3,
                'staff_limit' => 10,
                'property_manager_limit' => 0,
                'allow_company_profile' => 1,
                'allow_invoice_branding' => 1,
                'allow_roles_permissions' => 1,
                'allow_contact_login' => 1,
                'is_active' => 1,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            \DB::table('plans')->where('id', $planId)->update(['allow_roles_permissions' => 1]);
        }

        \DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createAccountUser(string $roleName, string $accountType, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' RBAC '.uniqid(),
            'email' => 'rbac-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => $accountType,
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
