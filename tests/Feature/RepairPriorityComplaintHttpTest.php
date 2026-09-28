<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Models\RepairIssue;
use App\Models\RepairSlaEvent;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepairPriorityComplaintHttpTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_skipping_the_list_keeps_a_normal_repair(): void
    {
        Storage::fake('public');
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('What is wrong?', false)
            ->assertSee('name="complaint_code"', false)
            ->assertSee('Something else', false)
            ->assertSee('Gas leak or suspected escape', false)
            ->assertSee('data-other-note hidden', false)
            ->assertDontSee('tp-complaint-grid', false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'Kitchen tap leaking onto the floor.',
                'priority' => 'high',
                'photo' => UploadedFile::fake()->image('leak.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertSame('high', $repair->priority);
        $this->assertNull($repair->complaint_code);
        $this->assertNull($repair->reported_at);
        $this->assertSame(0, RepairSlaEvent::query()->where('repair_issue_id', $repair->id)->count());

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair->id))
            ->assertOk()
            ->assertDontSee('Dispatch trade', false);
    }

    public function test_a_listed_problem_does_not_need_a_note(): void
    {
        Storage::fake('public');
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'complaint_code' => 'gas_leak',
                'photo' => UploadedFile::fake()->image('gas.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertSame('gas_leak', $repair->complaint_code);
        $this->assertSame('Gas leak or suspected escape', $repair->description);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'complaint_code' => 'other',
                'photo' => UploadedFile::fake()->image('other.jpg'),
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_priority_complaint_sets_the_clock_and_ignores_the_tenant_priority(): void
    {
        Storage::fake('public');
        config(['crm_notifications.enabled' => true]);
        Carbon::setTestNow(Carbon::parse('2026-01-15 10:00:00', 'Europe/London'));

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'I can smell gas in the kitchen.',
                'priority' => 'low',
                'complaint_code' => 'gas_leak',
                'photo' => UploadedFile::fake()->image('gas.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'))
            ->assertSessionHas('flash_notification');

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertSame('gas_leak', $repair->complaint_code);
        $this->assertSame('critical', $repair->priority);
        $this->assertTrue($repair->emergency_access);
        $this->assertSame('make_safe_24', $repair->classification_snapshot['clock']);
        $this->assertSame(
            '2026-01-16 10:00',
            $repair->make_safe_due_at->timezone('Europe/London')->format('Y-m-d H:i')
        );
        $this->assertDatabaseHas('repair_sla_events', [
            'repair_issue_id' => $repair->id,
            'event' => 'reported',
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('Gas leak or suspected escape', false)
            ->assertSee('within 24 hours', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair->id))
            ->assertOk()
            ->assertSee('Dispatch trade', false)
            ->assertSee('Emergency entry is allowed', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.sla.store', [$repair, 'dispatched']))
            ->assertRedirect(route('admin.property_repairs.show', $repair->id));

        $repair->refresh();
        $dispatchedAt = $repair->dispatched_at->toIso8601String();
        $this->assertSame('Under Process', $repair->status);
        $this->assertSame((int) $landlord->id, (int) $repair->dispatched_by);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.sla.store', [$repair, 'dispatched']))
            ->assertRedirect(route('admin.property_repairs.show', $repair->id));

        $repair->refresh();
        $this->assertSame($dispatchedAt, $repair->dispatched_at->toIso8601String());
        $this->assertSame(1, RepairSlaEvent::query()->where('repair_issue_id', $repair->id)->where('event', 'dispatched')->count());

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.sla.store', [$repair, 'made_safe']))
            ->assertRedirect();
        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.sla.store', [$repair, 'resolved']))
            ->assertRedirect();

        $repair->refresh();
        $this->assertNotNull($repair->make_safe_at);
        $this->assertNotNull($repair->resolved_at);
        $this->assertSame('Completed', $repair->status);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('admin.property_repairs.sla.store', [$repair, 'dispatched']))
            ->assertForbidden();
    }

    public function test_unknown_complaint_is_rejected_and_contained_leak_uses_72_hours(): void
    {
        Storage::fake('public');
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'Not a real code',
                'complaint_code' => 'dripping_tap',
                'photo' => UploadedFile::fake()->image('tap.jpg'),
            ])
            ->assertSessionHasErrors('complaint_code');

        Carbon::setTestNow(Carbon::parse('2026-06-02 09:00:00', 'Europe/London'));
        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'A small leak under the sink.',
                'complaint_code' => 'contained_leak',
                'photo' => UploadedFile::fake()->image('leak.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertSame('hours_72', $repair->classification_snapshot['clock']);
        $this->assertFalse($repair->emergency_access);
        $this->assertNull($repair->make_safe_due_at);
        $this->assertSame(
            '2026-06-05 09:00',
            $repair->sla_due_at->timezone('Europe/London')->format('Y-m-d H:i')
        );

        $phrase = (string) $repair->classification_snapshot['tenant_phrase'];
        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee($phrase, false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair))
            ->assertOk()
            ->assertSee($phrase, false);
    }

    public function test_priority_clock_does_not_use_the_one_hour_acknowledge_rule(): void
    {
        Storage::fake('public');
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'Sparking socket.',
                'complaint_code' => 'electrical_hazard',
                'photo' => UploadedFile::fake()->image('spark.jpg'),
            ])
            ->assertRedirect();

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $repair->forceFill([
            'created_at' => now()->subHours(3),
            'make_safe_due_at' => now()->addHours(20),
        ])->save();

        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => now()->timezone('Europe/London')->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame(0, NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::RepairEscalated->value)
            ->where('subject_id', $repair->id)
            ->count());

        $repair->forceFill(['make_safe_due_at' => now()->subHour()])->save();
        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => now()->timezone('Europe/London')->format('Y-m-d H:i:s'),
        ]);

        $logs = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::RepairEscalated->value)
            ->where('subject_id', $repair->id)
            ->get();
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->contains(fn ($log) => (int) $log->notifiable_id === $landlord->id));
        $this->assertTrue($logs->every(fn ($log) => ($log->payload['milestone'] ?? null) === 'make-safe-due'));

        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => now()->timezone('Europe/London')->format('Y-m-d H:i:s'),
        ]);
        $this->assertSame($logs->count(), NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::RepairEscalated->value)
            ->where('subject_id', $repair->id)
            ->count());
    }

    public function test_open_summer_heating_upgrades_on_1_october(): void
    {
        Storage::fake('public');
        config(['crm_notifications.enabled' => true]);
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'Europe/London'));

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'The boiler is dead.',
                'complaint_code' => 'heating_loss',
                'photo' => UploadedFile::fake()->image('boiler.jpg'),
            ])
            ->assertRedirect();

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertSame('summer', $repair->classification_snapshot['season']);
        $this->assertSame('hours_72', $repair->classification_snapshot['clock']);

        Carbon::setTestNow();
        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => '2026-10-01 08:00:00',
        ]);

        $repair->refresh();
        $this->assertSame('winter', $repair->classification_snapshot['season']);
        $this->assertSame('make_safe_24', $repair->classification_snapshot['clock']);
        $this->assertTrue($repair->emergency_access);
        $this->assertSame(
            '2026-10-02 00:00',
            $repair->make_safe_due_at->timezone('Europe/London')->format('Y-m-d H:i')
        );
        $this->assertDatabaseHas('repair_sla_events', [
            'repair_issue_id' => $repair->id,
            'event' => 'heating_upgraded',
        ]);

        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => '2026-10-02 09:00:00',
        ]);
        $this->assertSame(1, RepairSlaEvent::query()
            ->where('repair_issue_id', $repair->id)
            ->where('event', 'heating_upgraded')
            ->count());
    }

    public function test_tenant_can_choose_a_visit_window_and_a_past_slot_is_rejected(): void
    {
        Storage::fake('public');
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('When can someone visit?', false)
            ->assertSee('Morning', false)
            ->assertSee('data-availability', false);

        $slot = now()->addDays(3)->startOfDay()->setTime(9, 0);
        $category = \App\Models\RepairCategory::query()
            ->whereNull('parent_id')
            ->where(function ($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('id')
            ->first();
        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'The kitchen tap drips.',
                'repair_category_id' => $category?->id,
                'access_details' => 'Buzzer 4',
                'tenant_availability' => $slot->format('Y-m-d\TH:i'),
                'photo' => UploadedFile::fake()->image('tap.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->latest('id')->first();
        $this->assertSame($slot->format('Y-m-d H:i'), $repair->tenant_availability->format('Y-m-d H:i'));
        $this->assertSame($slot->format('j M Y').', morning', $repair->tenantAvailabilityLabel());
        $this->assertSame('Buzzer 4', $repair->access_details);
        if ($category) {
            $this->assertSame((int) $category->id, (int) $repair->repair_category_id);
            $this->assertSame(['level_1' => (string) $category->id], json_decode($repair->repair_navigation, true));
        }

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('Available '.$repair->tenantAvailabilityLabel(), false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair->id))
            ->assertOk()
            ->assertSee('Tenant availability: '.$repair->tenantAvailabilityLabel(), false);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'Too late already.',
                'tenant_availability' => now()->subDay()->setTime(9, 0)->format('Y-m-d\TH:i'),
                'photo' => UploadedFile::fake()->image('old.jpg'),
            ])
            ->assertSessionHasErrors('tenant_availability');
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Priority Landlord',
            'email' => 'priority-landlord-'.uniqid().'@resisquare.test',
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

    /**
     * @return array{0: User}
     */
    private function createTenantOnAccount(int $accountId): array
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Priority Tenant',
            'first_name' => 'Riley',
            'email' => 'priority-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user];
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function createLet(int $accountId, User $landlord, User $tenant): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '4 Priority Street',
            'city' => 'London',
            'postcode' => 'E1 4AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1100,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return [$property, $tenancy];
    }
}
