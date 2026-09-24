<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessibilityBaselineHttpTest extends TestCase
{
    public function test_workspace_has_skip_link_and_login_has_labeled_fields(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('auth-field', false)
            ->assertSee('>Email</span>', false)
            ->assertSee('>Password</span>', false);

        [$user, $accountId] = $this->createLandlord();
        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('Skip to content', false)
            ->assertSee('href="#wrapper"', false);
    }

    /**
     * @return array{0: \App\Models\User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('Landlord', 'web');
        $user = \App\Models\User::create([
            'name' => 'A11y Landlord',
            'email' => 'a11y-'.uniqid().'@resisquare.test',
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
