<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyPlanLimitHttpTest extends TestCase
{
    public function test_landlord_cannot_open_create_when_property_limit_is_full(): void
    {
        [$user, $accountId] = $this->createLimitedLandlord(1);
        $this->insertProperty($accountId, $user->id);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.create'))
            ->assertRedirect(route('admin.properties.index'));
    }

    public function test_landlord_cannot_restore_property_when_limit_is_full(): void
    {
        [$user, $accountId] = $this->createLimitedLandlord(1);
        $this->insertProperty($accountId, $user->id);

        $deletedId = $this->insertProperty($accountId, $user->id, now());

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.properties.restore', ['id' => $deletedId]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLimitedLandlord(int $propertyLimit): array
    {
        Permission::findOrCreate('create properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create properties']);

        $user = User::create([
            'name' => 'Limit Landlord',
            'email' => 'limit-landlord-'.uniqid().'@resisquare.test',
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

        $planId = \DB::table('plans')->insertGetId([
            'code' => 'limit-test-'.uniqid(),
            'name' => 'Limit Test',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 0,
            'annual_price_minor' => 0,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => $propertyLimit,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'is_active' => 1,
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    private function insertProperty(int $accountId, int $userId, $deletedAt = null): int
    {
        return \DB::table('properties')->insertGetId([
            'account_id' => $accountId,
            'created_by' => $userId,
            'line_1' => 'Limit Street '.uniqid(),
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'deleted_at' => $deletedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
