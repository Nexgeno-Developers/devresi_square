<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantProfileHttpTest extends TestCase
{
    public function test_tenant_uses_portal_profile_not_staff_module(): void
    {
        [$tenant, $accountId] = $this->createTenant();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('admin.users.profile.show'))
            ->assertRedirect(route('tenant.profile'));

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.profile'))
            ->assertOk()
            ->assertSee('data-tenant-profile="1"', false)
            ->assertSee('Contact details', false)
            ->assertSee('Password', false)
            ->assertDontSee('Back to Users', false)
            ->assertDontSee('Edit User Profile', false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.profile.update'), [
                'first_name' => 'Tina',
                'last_name' => 'Tenant',
                'email' => $tenant->email,
                'phone' => '07700900000',
            ])
            ->assertRedirect(route('tenant.profile'));

        $tenant->refresh();
        $this->assertSame('Tina', $tenant->first_name);
        $this->assertSame('07700900000', $tenant->phone);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.profile.password'), [
                'current_password' => 'password',
                'new_password' => 'NewPass123!',
                'new_password_confirmation' => 'NewPass123!',
            ])
            ->assertRedirect(route('tenant.profile'));

        $this->assertTrue(Hash::check('NewPass123!', $tenant->fresh()->password));

        $tenant->forceFill(['first_name' => '', 'last_name' => '', 'name' => 'Sabir Sayyed'])->save();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.profile'))
            ->assertOk()
            ->assertSee('value="Sabir"', false)
            ->assertSee('value="Sayyed"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.notifications'))
            ->assertOk()
            ->assertSee('data-tenant-notifications="1"', false)
            ->assertDontSee('Account defaults', false)
            ->assertDontSee('View delivery log', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createTenant(): array
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Portal Tenant',
            'first_name' => 'Portal',
            'last_name' => 'Tenant',
            'email' => 'tenant-profile-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }
}
