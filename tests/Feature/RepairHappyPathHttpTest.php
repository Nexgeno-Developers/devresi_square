<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairPhoto;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepairHappyPathHttpTest extends TestCase
{
    public function test_tenant_photo_raise_notifies_landlord_and_status_updates_round_trip(): void
    {
        config(['crm_notifications.enabled' => true]);
        Queue::fake();
        Storage::fake('public');

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $photo = UploadedFile::fake()->image('leak.jpg', 640, 480);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'Kitchen tap leaking onto the floor.',
                'priority' => 'high',
                'photo' => $photo,
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->first();
        $this->assertNotNull($repair);
        $this->assertSame('Pending', $repair->status);
        $this->assertSame('Kitchen tap leaking onto the floor.', $repair->description);

        $photoRow = RepairPhoto::query()->where('repair_issue_id', $repair->id)->first();
        $this->assertNotNull($photoRow);
        $upload = Upload::query()->find((int) $photoRow->photos);
        $this->assertNotNull($upload);
        $this->assertSame('image', $upload->type);

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::RepairReported->value,
            'notifiable_id' => $landlord->id,
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair->id))
            ->assertOk()
            ->assertSee('Kitchen tap leaking', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.saveForm'), [
                'repair_id' => $repair->id,
                'form_type' => 'property_issue_details',
                'description' => $repair->description,
                'priority' => 'high',
                'status' => 'Under Process',
                'sub_status' => 'Under Process',
                'estimated_price' => '85.50',
                'repair_navigation' => $repair->repair_navigation,
                'repair_category_id' => $repair->repair_category_id,
                'property_id' => $repair->property_id,
            ])
            ->assertOk();

        $repair->refresh();
        $this->assertSame('Under Process', $repair->status);
        $this->assertEquals(85.5, (float) $repair->estimated_price);

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::RepairStatusChanged->value,
            'notifiable_id' => $tenant->id,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('In progress', false)
            ->assertDontSee('Under Process', false)
            ->assertSee('data-tenant-maintenance="1"', false)
            ->assertSee('Take or choose a photo', false);

        Queue::assertPushed(SendNotificationJob::class);

        $tenantNotice = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::RepairStatusChanged->value)
            ->where('notifiable_id', $tenant->id)
            ->where('subject_id', $repair->id)
            ->first();
        $this->assertSame(route('tenant.maintenance'), $tenantNotice?->payload['action_url'] ?? null);
    }

    public function test_repair_handoff_shows_the_same_job_to_landlord_and_tenant(): void
    {
        config(['crm_notifications.enabled' => true]);
        Storage::fake('public');

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [$previous] = $this->createTenantOnAccount($accountId);
        [$property, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $category = RepairCategory::create([
            'name' => 'Kitchen',
            'parent_id' => null,
            'level' => 1,
            'description' => 'Kitchen',
            'status' => 1,
            'position' => 0,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $property->id,
                'description' => 'Kitchen tap leaking onto the floor.',
                'priority' => 'high',
                'photo' => UploadedFile::fake()->image('leak.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->forAccount($accountId)->firstOrFail();
        $upload = Upload::query()->findOrFail((int) RepairPhoto::query()->where('repair_issue_id', $repair->id)->value('photos'));
        $photoUrl = str_starts_with((string) $upload->file_name, 'private:')
            ? '/aiz-uploader/download/'.$upload->id
            : basename((string) $upload->file_name);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Kitchen tap leaking onto the floor.',
                $repair->reference_number,
            ], false)
            ->assertSee('12 Repair Road', false)
            ->assertSee('High', false)
            ->assertSee($photoUrl, false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee($photoUrl, false)
            ->assertSee('data-repair-open="'.$repair->id.'"', false);

        $reported = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::RepairReported->value)
            ->where('subject_id', $repair->id)
            ->get();
        $tenantReported = $reported->first(fn ($log) => (int) $log->notifiable_id === $tenant->id);
        $landlordReported = $reported->first(fn ($log) => (int) $log->notifiable_id === $landlord->id);
        $this->assertSame(route('tenant.maintenance'), $tenantReported->payload['action_url'] ?? null);
        $this->assertSame(route('admin.property_repairs.show', $repair), $landlordReported->payload['action_url'] ?? null);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('data-dash-label="Open repairs">1', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('data-open-repairs="1"', false);

        RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenant_id' => $previous->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
            'description' => 'Previous tenant boiler fault',
            'status' => 'Pending',
            'reference_number' => 'OLD-'.$accountId,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertDontSee('Previous tenant boiler fault', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.store'), [
                'property_id' => $property->id,
                'repair_category_id' => $category->id,
                'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
                'description' => 'Landlord noticed a dripping overflow.',
            ])
            ->assertRedirect();

        $raised = RepairIssue::query()->forAccount($accountId)->where('description', 'Landlord noticed a dripping overflow.')->firstOrFail();
        $this->assertSame($tenant->id, (int) $raised->tenant_id);
        $this->assertSame($accountId, (int) $raised->account_id);
        $this->assertSame($property->id, (int) $raised->property_id);
        $this->assertSame($category->id, (int) $raised->repair_category_id);
        $this->assertSame(0, $raised->repairPhotos()->count());

        $landlordUpload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => 'overflow',
            'extension' => 'jpg',
            'file_name' => 'repairs/overflow-'.$accountId.'.jpg',
            'user_id' => $landlord->id,
            'type' => 'image',
            'file_size' => 100,
        ]);
        RepairPhoto::create([
            'repair_issue_id' => $raised->id,
            'photos' => (string) $landlordUpload->id,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('Landlord noticed a dripping overflow.', false)
            ->assertSee('repairs/overflow-'.$accountId.'.jpg', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $raised))
            ->assertOk()
            ->assertSee('repairs/overflow-'.$accountId.'.jpg', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.saveForm'), [
                'repair_id' => $repair->id,
                'form_type' => 'property_issue_details',
                'description' => $repair->description,
                'priority' => 'high',
                'status' => 'Closed',
                'sub_status' => 'Pending',
                'estimated_price' => '0',
                'repair_navigation' => $repair->repair_navigation,
                'repair_category_id' => $repair->repair_category_id,
                'property_id' => $repair->property_id,
                'landlord_note' => 'Plumber booked for Friday.',
            ])
            ->assertOk();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('data-repair-history="'.$repair->id.'"', false)
            ->assertDontSee('data-repair-open="'.$repair->id.'"', false)
            ->assertSee('Plumber booked for Friday.', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('data-dash-label="Open repairs">2', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('data-open-repairs="1"', false);
    }

    public function test_tenant_area_list_skips_fixture_names_and_duplicate_labels(): void
    {
        RepairCategory::create([
            'name' => 'Handoff '.uniqid(),
            'parent_id' => null,
            'level' => 1,
            'description' => 'Test',
            'status' => 1,
            'position' => 0,
        ]);
        RepairCategory::create([
            'name' => 'Kitchen',
            'parent_id' => null,
            'level' => 1,
            'description' => 'Test',
            'status' => 1,
            'position' => 0,
        ]);

        $names = RepairCategory::tenantAreaChoices()->pluck('name');

        $this->assertFalse($names->contains(fn ($name) => str_starts_with($name, 'Handoff ')));
        $this->assertFalse($names->contains('Isolation test'));
        $this->assertSame(1, $names->filter(fn ($name) => $name === 'Kitchen')->count());
    }

    public function test_tenant_can_raise_repair_without_photo(): void
    {
        Storage::fake('public');

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $tenancy->property_id,
                'description' => 'No photo attached',
                'complaint_code' => 'other',
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $repair = RepairIssue::query()->where('description', 'No photo attached')->first();
        $this->assertNotNull($repair);
        $this->assertSame(0, RepairPhoto::query()->where('repair_issue_id', $repair->id)->count());
    }

    public function test_landlord_raise_form_saves_the_edit_fields_and_photo(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenantOnAccount($accountId);
        [$property] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $category = RepairCategory::create([
            'name' => 'Bathroom',
            'parent_id' => null,
            'level' => 1,
            'description' => 'Bathroom',
            'status' => 1,
            'position' => 0,
        ]);
        $when = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.create'))
            ->assertOk()
            ->assertSee('name="priority"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="sub_status"', false)
            ->assertSee('name="tenant_id"', false)
            ->assertSee('name="tenant_availability"', false)
            ->assertSee('name="access_details"', false)
            ->assertSee('name="estimated_price"', false)
            ->assertSee('name="vat_type"', false)
            ->assertSee('name="repair_photo"', false)
            ->assertDontSee('data-toggle="aizuploader"', false);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.store'), [
                'property_id' => $property->id,
                'repair_category_id' => $category->id,
                'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
                'description' => 'Bathroom fan is stuck.',
                'priority' => 'high',
                'status' => 'Reported',
                'sub_status' => 'Quoted',
                'tenant_id' => $tenant->id,
                'tenant_availability' => $when,
                'access_details' => 'Key safe 1948',
                'estimated_price' => '150.50',
                'vat_type' => 'exclusive',
                'repair_photo' => UploadedFile::fake()->image('fan.jpg'),
            ])
            ->assertRedirect();

        $repair = RepairIssue::query()
            ->where('account_id', $accountId)
            ->where('description', 'Bathroom fan is stuck.')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('high', $repair->priority);
        $this->assertSame('Reported', $repair->status);
        $this->assertSame('Quoted', $repair->sub_status);
        $this->assertSame($tenant->id, (int) $repair->tenant_id);
        $this->assertSame($when, $repair->tenant_availability?->format('Y-m-d\TH:i'));
        $this->assertSame('Key safe 1948', $repair->access_details);
        $this->assertSame('150.50', number_format((float) $repair->estimated_price, 2, '.', ''));
        $this->assertSame('exclusive', $repair->vat_type);
        $this->assertSame(1, $repair->repairPhotos()->count());
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Repair Landlord',
            'email' => 'repair-landlord-'.uniqid().'@resisquare.test',
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
            'name' => 'Repair Tenant',
            'first_name' => 'Riley',
            'email' => 'repair-tenant-'.uniqid().'@resisquare.test',
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
            'line_1' => '12 Repair Road',
            'city' => 'London',
            'postcode' => 'E1 1AA',
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
