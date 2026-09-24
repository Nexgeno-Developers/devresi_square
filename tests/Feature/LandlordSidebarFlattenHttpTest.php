<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordSidebarFlattenHttpTest extends TestCase
{
    public function test_landlord_sidebar_is_flat_with_page_chips_on_tenancies(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertDontSee('Search menu', false)
            ->assertDontSee('propertiesSubmenu', false)
            ->assertDontSee('tenanciesSubmenu', false)
            ->assertDontSee('repairSubmenu', false)
            ->assertDontSee('peopleSubmenu', false)
            ->assertSee('People', false)
            ->assertSee('Repairs', false)
            ->assertSee('Settings', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.all'))
            ->assertOk()
            ->assertSee('lw-chip-filters', false)
            ->assertSee('Terminated', false)
            ->assertSee('Add tenancy', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Landlord Sidebar',
            'email' => 'landlord-sidebar-'.uniqid().'@resisquare.test',
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
