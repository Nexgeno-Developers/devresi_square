<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Saas\PortalAccessService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordMembershipDetectionTest extends TestCase
{
    public function test_paying_landlord_owner_is_landlord_plan_user_not_portal(): void
    {
        [$user, $accountId] = $this->createMembershipUser('Landlord', 'landlord', 'owner');

        $this->actingAs($user)->withSession(['current_account_id' => $accountId]);

        $this->assertTrue(is_landlord_plan_user($user));
        $this->assertFalse(app(PortalAccessService::class)->isPortalUser($user, $accountId));
    }

    public function test_mis_tagged_landlord_account_owner_is_still_not_a_portal_user(): void
    {
        [$user, $accountId] = $this->createMembershipUser('Landlord', 'landlord', 'landlord');

        $this->actingAs($user)->withSession(['current_account_id' => $accountId]);

        $this->assertFalse(app(PortalAccessService::class)->isPortalUser($user, $accountId));
        $this->assertTrue(is_landlord_plan_user($user));
    }

    public function test_tenant_on_landlord_account_is_portal_not_landlord_plan_user(): void
    {
        $landlord = User::create([
            'name' => 'Landlord Owner '.uniqid(),
            'email' => 'landlord-owner-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        Role::findOrCreate('Landlord', 'web');
        $landlord->assignRole('Landlord');

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Tenant '.uniqid(),
            'email' => 'tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['current_account_id' => $accountId]);

        $this->assertFalse(is_landlord_plan_user($user));
        $this->assertTrue(app(PortalAccessService::class)->isPortalUser($user, $accountId));
    }

    public function test_agency_landlord_contact_is_portal_not_landlord_plan_user(): void
    {
        [$user, $accountId] = $this->createMembershipUser('Landlord', 'estate_agent_company', 'landlord');

        $this->actingAs($user)->withSession(['current_account_id' => $accountId]);

        $this->assertFalse(is_landlord_plan_user($user));
        $this->assertTrue(app(PortalAccessService::class)->isPortalUser($user, $accountId));
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createMembershipUser(string $roleName, string $accountType, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::create([
            'name' => $roleName.' '.uniqid(),
            'email' => strtolower($roleName).'-'.uniqid().'@resisquare.test',
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
