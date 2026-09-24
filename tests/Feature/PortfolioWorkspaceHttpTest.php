<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 18: portfolio by workspace, short titles, photos.
 */
class PortfolioWorkspaceHttpTest extends TestCase
{
    public function test_workspace_co_owner_sees_home_with_short_title_and_photo_thumb(): void
    {
        Storage::fake('public');
        Permission::findOrCreate('create properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo('create properties');

        $owner = User::create([
            'name' => 'Portfolio Owner',
            'email' => 'portfolio-owner-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $owner->assignRole($role);

        $colleague = User::create([
            'name' => 'Portfolio Colleague',
            'email' => 'portfolio-colleague-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $colleague->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $owner->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$owner, $colleague] as $member) {
            DB::table('account_users')->insert([
                'account_id' => $accountId,
                'user_id' => $member->id,
                'member_type' => 'owner',
                'access_level' => 'full',
                'can_login' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => 'front',
            'file_name' => 'uploads/front.png',
            'user_id' => $owner->id,
            'extension' => 'png',
            'type' => 'image',
            'file_size' => 12,
        ]);
        Storage::disk('public')->put('uploads/front.png', 'fake');

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $owner->id,
            'prop_ref_no' => 'RESISQP-HIDDEN-'.uniqid(),
            'prop_name' => 'Flat 108',
            'line_1' => 'Flat 108',
            'line_2' => '1 Baltimore Wharf',
            'city' => 'London',
            'postcode' => 'E14 9RU',
            'photos' => (string) $upload->id,
        ]);

        $this->assertSame('Flat 108', $property->short_title);
        $this->assertStringContainsString('Baltimore Wharf', $property->short_address);
        $this->assertStringContainsString('E14 9RU', $property->short_address);
        $this->assertStringNotContainsString('RESISQP', $property->display_label);

        $response = $this->actingAs($colleague)
            ->withSession(['current_account_id' => $accountId])
            ->followingRedirects()
            ->get(route('admin.properties.index'));

        $response->assertOk();
        $response->assertSee('Flat 108', false);
        $response->assertSee('Baltimore Wharf', false);
        $response->assertSee('E14 9RU', false);
        $response->assertDontSee($property->prop_ref_no, false);
        $response->assertSee('Photos', false);
    }
}
