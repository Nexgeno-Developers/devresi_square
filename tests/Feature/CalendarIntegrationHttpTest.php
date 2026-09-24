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
            ->assertDontSee('All Offices', false)
            ->assertDontSee('Buyer Viewing', false)
            ->assertDontSee('Valuation', false)
            ->assertDontSee('Mortgage Appointment', false)
            ->assertSee('Inspection', false);
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
                'visible_to_tenant' => 1,
            ])
            ->assertOk();

        $event = Event::query()->forAccount($accountId)->where('title', 'Mid-term inspection')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->users()->where('users.id', $tenant->id)->exists());

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.calendar'))
            ->assertOk()
            ->assertSee('Mid-term inspection', false)
            ->assertSee('data-tenant-calendar="1"', false);
    }

    public function test_event_reminder_creates_email_and_in_app_notification_logs(): void
    {
        config(['crm_notifications.enabled' => true]);

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
            'line_1' => '9 Reminder Road',
            'city' => 'London',
            'postcode' => 'E1 4AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
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
            ['slug' => 'inspection-reminder-test', 'description' => 'Inspection']
        );
        $subType = EventSubType::query()->firstOrCreate(
            ['event_type_id' => $type->id, 'name' => 'Property Inspection'],
            ['slug' => 'property-inspection-reminder-test', 'description' => 'Inspection']
        );

        $start = now()->addMinutes(45);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('backend.events.store'), [
                'title' => 'Reminder visit',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => $start->format('Y-m-d H:i:s'),
                'end_datetime' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
                'visible_to_tenant' => 1,
                'reminders' => [
                    ['minutes_before' => 60, 'channel' => 'email'],
                ],
            ])
            ->assertOk();

        $event = Event::query()->forAccount($accountId)->where('title', 'Reminder visit')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->reminders()->exists());

        \Illuminate\Support\Facades\Artisan::call('events:send-reminders');

        $this->assertDatabaseHas('event_reminders', [
            'event_id' => $event->id,
            'sent' => 1,
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => \App\Enums\CrmNotificationEvent::AppointmentReminder->value,
            'channel' => 'email',
            'notifiable_id' => $tenant->id,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => \App\Enums\CrmNotificationEvent::AppointmentReminder->value,
            'channel' => 'system',
            'notifiable_id' => $tenant->id,
        ]);

        $tenantNotice = \App\Models\NotificationLog::query()
            ->where('account_id', $accountId)
            ->where('identifier', \App\Enums\CrmNotificationEvent::AppointmentReminder->value)
            ->where('notifiable_id', $tenant->id)
            ->first();
        $this->assertSame(route('tenant.calendar'), $tenantNotice->payload['action_url'] ?? null);
        $this->assertNotSame(route('backend.events.calendar'), $tenantNotice->payload['action_url'] ?? null);
    }

    public function test_landlord_only_and_contractor_visits_stay_off_the_tenant_calendar(): void
    {
        [$landlord, $accountId, $tenant, $property] = $this->createHousehold();
        $session = ['current_account_id' => $accountId];
        [$type, $subType] = $this->inspectionType();

        $contractorRole = Role::findOrCreate('Contractor', 'web');
        $contractor = User::create([
            'name' => 'Boiler Contractor',
            'email' => 'calendar-contractor-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $contractor->assignRole($contractorRole);
        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $contractor->id,
            'member_type' => 'contractor',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start = now()->addDays(4);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.store'), [
                'title' => 'Landlord accounts review',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => $start->format('Y-m-d H:i:s'),
                'end_datetime' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
            ])
            ->assertOk();

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.store'), [
                'title' => 'Contractor boiler service',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => $start->copy()->addDay()->format('Y-m-d H:i:s'),
                'end_datetime' => $start->copy()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
                'invite_ids' => [$contractor->id],
            ])
            ->assertOk();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.calendar', ['month' => $start->format('Y-m')]))
            ->assertOk()
            ->assertDontSee('Landlord accounts review', false)
            ->assertDontSee('Contractor boiler service', false);
    }

    public function test_cancel_removes_the_visit_and_reschedule_moves_the_day_once(): void
    {
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId, $tenant, $property] = $this->createHousehold();
        $session = ['current_account_id' => $accountId];
        [$type, $subType] = $this->inspectionType();

        $start = now()->addDays(2)->startOfHour();
        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.store'), [
                'title' => 'Shared inspection',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => $start->format('Y-m-d H:i:s'),
                'end_datetime' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
                'invite_ids' => [$tenant->id],
            ])
            ->assertOk();

        $event = Event::query()->forAccount($accountId)->where('title', 'Shared inspection')->firstOrFail();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.calendar', ['month' => $start->format('Y-m')]))
            ->assertOk()
            ->assertSee('data-cal-event="'.$event->id.'"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('data-upcoming-event="'.$event->id.'"', false);

        $moved = $start->copy()->addMonth()->startOfMonth()->addDays(4)->setTime(10, 0);
        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.updateInstance', $event->id), [
                'start_datetime' => $moved->format('Y-m-d H:i:s'),
                'end_datetime' => $moved->copy()->addHour()->format('Y-m-d H:i:s'),
                'form_action' => 'updateInstance',
            ])
            ->assertOk();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.calendar', ['month' => $start->format('Y-m')]))
            ->assertOk()
            ->assertDontSee('data-cal-event="'.$event->id.'"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.calendar', ['month' => $moved->format('Y-m')]))
            ->assertOk()
            ->assertSee('data-cal-event="'.$event->id.'"', false)
            ->assertSee('10:00 Shared inspection', false);

        $notices = \App\Models\NotificationLog::query()
            ->where('identifier', \App\Enums\CrmNotificationEvent::AppointmentRescheduled->value)
            ->where('subject_id', $event->id)
            ->where('notifiable_id', $tenant->id)
            ->get();
        $this->assertNotEmpty($notices);
        $this->assertCount(1, $notices->pluck('payload.milestone')->unique()->filter());
        $this->assertSame(route('tenant.calendar'), $notices->first()->payload['action_url'] ?? null);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.changeStatus', $event->id), [
                'status' => 'cancelled',
            ])
            ->assertOk();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.calendar', ['month' => $moved->format('Y-m')]))
            ->assertOk()
            ->assertDontSee('data-cal-event="'.$event->id.'"', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('data-upcoming-event="'.$event->id.'"', false);
    }

    /**
     * @return array{0: User, 1: int, 2: User, 3: Property}
     */
    private function createHousehold(): array
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
            'line_1' => '4 Visit Lane',
            'city' => 'London',
            'postcode' => 'N1 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'confirmed',
        ]);

        return [$landlord, $accountId, $tenant, $property];
    }

    /**
     * @return array{0: EventType, 1: EventSubType}
     */
    private function inspectionType(): array
    {
        $type = EventType::query()->firstOrCreate(
            ['name' => 'Inspection'],
            ['slug' => 'inspection-handoff', 'description' => 'Inspection']
        );
        $subType = EventSubType::query()->firstOrCreate(
            ['event_type_id' => $type->id, 'name' => 'Property Inspection'],
            ['slug' => 'property-inspection-handoff', 'description' => 'Inspection']
        );

        return [$type, $subType];
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
