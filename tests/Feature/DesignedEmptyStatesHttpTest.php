<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignedEmptyStatesHttpTest extends TestCase
{
    public function test_empty_lists_show_designed_states_not_blank_tables(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('lw-empty', false)
            ->assertSee('Nothing to invoice yet', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.all'))
            ->assertOk()
            ->assertSee('lw-empty', false)
            ->assertSee('No tenancies yet', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee('lw-empty', false)
            ->assertSee('No documents yet', false);

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.property_repairs.index'))
            ->assertOk()
            ->assertSee('lw-empty', false)
            ->assertSee('No repairs yet', false);
    }

    public function test_branded_error_pages_render(): void
    {
        $this->get('/this-route-definitely-does-not-exist-xyz')
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('Resisquare', false);
    }

    /**
     * @return array{0: \App\Models\User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('Landlord', 'web');
        $user = \App\Models\User::create([
            'name' => 'Empty States Landlord',
            'email' => 'empty-states-'.uniqid().'@resisquare.test',
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
