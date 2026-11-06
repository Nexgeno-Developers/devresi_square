<?php

namespace Tests\Feature;

use App\Models\OwnerGroup;
use App\Models\Property;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerGroupHttpTest extends TestCase
{
    public function test_property_keeps_one_owner_and_can_add_another(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '4 Owner Lane',
            'city' => 'London',
            'postcode' => 'N1 4OW',
        ]);
        $session = ['current_account_id' => $accountId];
        $group = OwnerGroup::create([
            'property_id' => $property->id,
            'purchased_date' => now()->toDateString(),
            'status' => 'active',
            'added_by' => $landlord->id,
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'owners']))
            ->assertOk()
            ->assertSee('Add owner', false)
            ->assertDontSee('Delete Owner Group', false);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.owner-groups.delete_group', $group->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A property needs at least one owner.');

        $this->assertNull($group->fresh()->deleted_at);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.owner-groups.create', ['property_id' => $property->id]))
            ->assertOk()
            ->assertSee('value="'.$property->id.'" selected', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.owner-groups.store'), [
                'user_id' => $landlord->id,
                'property_id' => $property->id,
                'purchased_date' => now()->toDateString(),
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.properties.index', [
                'property_id' => $property->id,
                'tabname' => 'owners',
            ]));

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'owners']))
            ->assertOk()
            ->assertSee('Delete Owner Group', false);

        $second = OwnerGroup::query()->where('property_id', $property->id)->where('id', '!=', $group->id)->firstOrFail();

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.owner-groups.delete_group', $second->id))
            ->assertRedirect();

        $this->assertNotNull($second->fresh()->deleted_at);
        $this->assertNull($group->fresh()->deleted_at);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Owner Landlord',
            'email' => 'owner-landlord-'.uniqid().'@resisquare.test',
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
