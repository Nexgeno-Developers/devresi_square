<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandedAuthHttpTest extends TestCase
{
    public function test_login_uses_branded_auth_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in · Resisquare', false)
            ->assertSee('auth-brand', false)
            ->assertSee('Homes, rent and repairs', false)
            ->assertSee('Sign up', false)
            ->assertDontSee('gradient-custom', false);
    }

    public function test_forgot_password_uses_branded_auth_shell(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset password · Resisquare', false)
            ->assertSee('auth-brand', false)
            ->assertSee('Send reset link', false);
    }

    public function test_finance_page_sets_browser_title(): void
    {
        [$user, $accountId] = $this->createLandlord();

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('<title>Finance · Resisquare</title>', false);
    }

    /**
     * @return array{0: \App\Models\User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('Landlord', 'web');
        $user = \App\Models\User::create([
            'name' => 'Auth Brand Landlord',
            'email' => 'auth-brand-'.uniqid().'@resisquare.test',
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
