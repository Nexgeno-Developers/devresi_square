<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordOnboardingHttpTest extends TestCase
{
    public function test_confirmed_landlord_sees_onboarding_overlay_on_dashboard(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $response->assertOk();
        $response->assertSee('lob-root', false);
        $response->assertSee('Add your first property', false);
        $response->assertSee('Find the property', false);
        $response->assertSee('Test mode: try', false);
    }

    public function test_onboarding_search_returns_chimnie_test_results(): void
    {
        config(['chimnie.test_mode' => true]);
        Http::fake([
            'api.postcodes.io/*' => Http::response([
                'result' => [
                    'postcode' => 'SW1A 1AA',
                    'region' => 'London',
                    'post_town' => 'LONDON',
                    'admin_district' => 'Westminster',
                ],
            ], 200),
        ]);

        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->getJson(route('admin.onboarding.landlord.search', ['postcode' => 'SW1A 1AA']));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $this->assertNotEmpty($response->json('data'));
        $this->assertSame('Buckingham Palace', $response->json('data.0.line_1'));
    }

    public function test_landlord_with_no_properties_sees_onboarding_on_properties_index(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.index'));

        $response->assertOk();
        $response->assertSee('lob-root', false);
        $response->assertSee('Add your first property', false);
    }

    public function test_completed_landlord_with_no_properties_still_sees_onboarding(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();
        \DB::table('accounts')->where('id', $accountId)->update([
            'onboarding_completed_at' => now(),
        ]);

        $dashboard = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('lob-root', false);

        $properties = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.index'));

        $properties->assertOk();
        $properties->assertSee('lob-root', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createConfirmedLandlord(): array
    {
        Permission::findOrCreate('create properties', 'web');
        Permission::findOrCreate('edit properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create properties', 'edit properties']);

        $user = User::create([
            'name' => 'Onboarding Landlord',
            'email' => 'onboarding-test-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'onboarding_step' => 1,
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

        \DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => \DB::table('plans')->value('id') ?: 1,
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }
}
