<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSubType;
use App\Models\EventType;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarIntegrationHttpTest extends TestCase
{
    public function test_landlord_can_open_account_calendar(): void
    {
        [$user, $accountId] = $this->createPortalUser('Landlord', 'owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.events.calendar'))
            ->assertOk()
            ->assertSee('Appointments', false)
            ->assertDontSee('All Offices', false);
    }

    public function test_landlord_event_on_property_is_visible_to_household_in_portal(): void
    {
        [$landlord, $accountId] = $this->createPortalUser('Landlord', 'owner');
        [$tenant] = $this->createPortalUser('Tenant', 'tenant');

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '12 Calendar Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        $type = EventType::query()->firstOrCreate(
            ['name' => 'Inspection'],
            ['slug' => 'inspection-calendar-test', 'description' => 'Inspection']
        );
        $subType = EventSubType::query()->firstOrCreate(
            ['event_type_id' => $type->id, 'name' => 'Property Inspection'],
            ['slug' => 'property-inspection-calendar-test', 'description' => 'Inspection']
        );

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('backend.events.store'), [
                'title' => 'Mid-term inspection',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'end_datetime' => now()->addDays(3)->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
            ])
            ->assertOk();

        $event = Event::query()->forAccount($accountId)->where('title', 'Mid-term inspection')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->users()->where('users.id', $tenant->id)->exists());

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.calendar'))
            ->assertOk()
            ->assertSee('Mid-term inspection', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPortalUser(string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' Calendar',
            'first_name' => $roleName,
            'email' => 'calendar-'.uniqid().'@resisquare.test',
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
            'member_type' => $memberType,
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }
}
