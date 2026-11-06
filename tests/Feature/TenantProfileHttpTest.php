<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
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
            ->assertSee('Notifications · Resisquare', false)
            ->assertSee('data-tenant-notifications="1"', false)
            ->assertDontSee('Account defaults', false)
            ->assertDontSee('View delivery log', false);
    }

    public function test_registered_tenant_does_not_see_a_portal_invite_notice(): void
    {
        [$tenant, $accountId] = $this->createTenant();
        $session = ['current_account_id' => $accountId];

        NotificationLog::create([
            'account_id' => $accountId,
            'identifier' => 'tenant.portal_invite',
            'notifiable_type' => $tenant->getMorphClass(),
            'notifiable_id' => $tenant->id,
            'notification_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'idempotency_key' => hash('sha256', uniqid('invite', true)),
            'channel' => 'system',
            'subject' => 'Portal invite',
            'message' => 'You have been invited to the tenant portal.',
            'payload' => ['action_url' => null],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        NotificationLog::create([
            'account_id' => $accountId,
            'identifier' => 'repair.reported',
            'notifiable_type' => $tenant->getMorphClass(),
            'notifiable_id' => $tenant->id,
            'notification_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'idempotency_key' => hash('sha256', uniqid('repair', true)),
            'channel' => 'system',
            'subject' => 'Repair update',
            'message' => 'A plumber is booked.',
            'payload' => ['action_url' => route('tenant.maintenance')],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.notifications'))
            ->assertOk()
            ->assertDontSee('Portal invite', false)
            ->assertSee('Repair update', false)
            ->assertSee(route('tenant.maintenance'), false);
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
