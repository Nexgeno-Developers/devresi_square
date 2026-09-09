<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordPropertyWizardHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['landlord_mvp.wizard_enabled' => true]);
    }

    public function test_landlord_can_access_property_passport_wizard_step_one(): void
    {
        [$user, $accountId] = $this->createLandlordUser();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.landlord_wizard.show'));

        $response->assertOk();
        $response->assertSee('Property Passport setup', false);
        $response->assertSee('Step 01', false);
        $response->assertSee('Find your property', false);
        $response->assertSee('Continue to property details', false);
    }

    public function test_landlord_can_complete_three_step_wizard(): void
    {
        [$user, $accountId] = $this->createLandlordUser();
        $countryId = Country::query()->value('id');
        if (! $countryId) {
            $countryId = \DB::table('countries')->insertGetId([
                'name' => 'United Kingdom',
                'code' => 'GB',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $session = ['current_account_id' => $accountId];
        $line1 = '99 Wizard Test Street '.uniqid();

        $step1 = $this->actingAs($user)
            ->withSession($session)
            ->post(route('admin.properties.landlord_wizard.store'), [
                'step' => 1,
                'line_1' => $line1,
                'city' => 'London',
                'postcode' => 'W1A 1AA',
                'country' => $countryId,
            ]);

        $step1->assertRedirect();
        $propertyId = Property::query()->where('line_1', $line1)->value('id');
        $this->assertNotNull($propertyId);

        $property = Property::find($propertyId);
        $this->assertSame($user->id, (int) $property->created_by);
        $this->assertSame($accountId, (int) $property->account_id);

        $step2 = $this->actingAs($user)
            ->withSession($session)
            ->post(route('admin.properties.landlord_wizard.store'), [
                'step' => 2,
                'property_id' => $propertyId,
                'specific_property_type' => 'flat',
                'property_type' => 'lettings',
                'bedroom' => '2',
                'bathroom' => '1',
                'tenure' => 'Leasehold',
                'epc_rating' => 'C',
            ]);

        $step2->assertRedirect(route('admin.properties.landlord_wizard.step', [
            'step' => 3,
            'property_id' => $propertyId,
        ], false));

        $step3 = $this->actingAs($user)
            ->withSession($session)
            ->post(route('admin.properties.landlord_wizard.store'), [
                'step' => 3,
                'property_id' => $propertyId,
                'confirm' => '1',
            ]);

        $step3->assertRedirect(route('admin.properties.index', [
            'property_id' => $propertyId,
            'tabname' => 'property',
        ], false));

        $property->refresh();
        $this->assertSame(3, (int) $property->quick_step);
    }

    public function test_property_create_url_points_landlord_to_onboarding_flow(): void
    {
        [$user] = $this->createLandlordUser();
        $this->actingAs($user);

        $this->assertStringContainsString(
            'add_property=1',
            property_create_url()
        );
    }

    public function test_wizard_redirects_to_onboarding_flow_when_disabled(): void
    {
        config(['landlord_mvp.wizard_enabled' => false]);
        [$user, $accountId] = $this->createLandlordUser();

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.landlord_wizard.show'))
            ->assertRedirect(route('admin.properties.index', ['add_property' => 1]));
    }

    public function test_landlord_can_open_property_tab_without_view_properties_permission(): void
    {
        [$user, $accountId] = $this->createLandlordUser();
        Permission::findOrCreate('view properties', 'web');
        $role = Role::findByName('Landlord', 'web');
        $hadViewProperties = $role->hasPermissionTo('view properties');

        if ($hadViewProperties) {
            $role->revokePermissionTo('view properties');
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $this->assertFalse($user->fresh()->can('view properties'));

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $user->id,
            'line_1' => '12 Tab Access Street',
            'city' => 'London',
            'postcode' => 'EC1A 1BB',
        ]);

        try {
            $response = $this->actingAs($user)
                ->withSession(['current_account_id' => $accountId])
                ->get(route('admin.properties.index', [
                    'property_id' => $property->id,
                    'tabname' => 'property',
                ]));

            $response->assertOk();
        } finally {
            $role->givePermissionTo('view properties');
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlordUser(): array
    {
        Permission::findOrCreate('create properties', 'web');
        Permission::findOrCreate('edit properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create properties', 'edit properties']);

        $user = User::create([
            'name' => 'Test Landlord',
            'email' => 'wizard-test-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'status' => 'active',
            'onboarding_completed_at' => now(),
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
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Property::create([
            'account_id' => $accountId,
            'created_by' => $user->id,
            'line_1' => 'Existing Landlord Property',
            'city' => 'London',
            'postcode' => 'N1 9GU',
        ]);

        return [$user, $accountId];
    }
}
