<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Steps 3–5 kill switches.
 */
class LaunchKillSwitchesHttpTest extends TestCase
{
    public function test_public_maintenance_routes_are_gone(): void
    {
        $this->get('/storage-link')->assertNotFound();
        $this->get('/command/optimize-clear')->assertNotFound();
        $this->get('/test-sms')->assertNotFound();
    }

    public function test_landlord_cannot_open_smtp_or_env_key_update(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('smtp_settings.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->post(route('env_key_update.update'), [
                'types' => ['APP_KEY'],
                'APP_KEY' => 'base64:attacker',
            ])
            ->assertForbidden();
    }

    public function test_landlord_cannot_open_ui_lab(): void
    {
        [$user, $accountId] = $this->createLandlord();

        $this->actingAs($user)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.ui_lab'))
            ->assertForbidden();
    }

    public function test_non_local_forces_debug_and_debugbar_off(): void
    {
        $this->app['env'] = 'staging';
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertFalse((bool) config('app.debug'));
        $this->assertFalse((bool) config('debugbar.enabled'));
    }

    public function test_debug_registration_otp_route_is_closed_by_default(): void
    {
        config(['app.debug' => false]);
        putenv('REGISTRATION_OTP_DEBUG=false');

        $this->get(route('debug.registration.otp', ['email' => 'x@example.com']))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Kill Switch Landlord',
            'email' => 'kill-switch-'.uniqid().'@resisquare.test',
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
