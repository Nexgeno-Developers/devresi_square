<?php

namespace Tests\Feature;

use App\Models\BankDetails;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventSubType;
use App\Models\EventType;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use App\Services\Finance\RentFinanceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneHandoffHttpTest extends TestCase
{
    public function test_phone_pages_keep_the_bottom_bar_and_the_pay_repair_and_download_path(): void
    {
        Storage::fake('public');

        [$landlord, $accountId, $tenant, $property, $tenancy] = $this->createLet();
        $session = ['current_account_id' => $accountId];
        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 640,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        BankDetails::create([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'account_name' => 'Phone Landlord',
            'account_no' => '55667788',
            'sort_code' => '55-66-77',
            'bank_name' => 'Phone Bank',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $css = file_get_contents(public_path('asset/backend/css/tenant-portal.css'));
        $this->assertStringContainsString('overflow-x: hidden', $css);
        $this->assertStringContainsString('.tp-bottom-nav', $css);

        $rent = $this->actingAs($tenant)->withSession($session)->get(route('tenant.rent'));
        $rent->assertOk()
            ->assertSee('aria-label="Tenant phone navigation"', false)
            ->assertSee(route('tenant.rent.show', $invoice), false);
        $rentHtml = $rent->getContent();
        $this->assertTrue(
            str_contains($rentHtml, '55667788') || str_contains($rentHtml, '/portal/rent/'.$invoice->id.'/pay'),
            'The rent page should offer a card payment or the landlord bank details.'
        );

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $property->id,
                'description' => 'Phone tap drip',
                'priority' => 'medium',
                'photo' => UploadedFile::fake()->image('drip.jpg'),
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('aria-label="Tenant phone navigation"', false)
            ->assertSee('Phone tap drip', false);

        $path = 'uploads/all/phone-gas.pdf';
        Storage::disk('public')->put($path, 'phone-pdf');
        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => 'phone-gas',
            'file_name' => $path,
            'user_id' => $landlord->id,
            'extension' => 'pdf',
            'type' => 'document',
            'file_size' => 12,
        ]);
        $document = Document::create([
            'account_id' => $accountId,
            'documentable_id' => $property->id,
            'documentable_type' => $property->getMorphClass(),
            'upload_ids' => (string) $upload->id,
            'title' => 'Phone gas certificate',
            'visibility' => 'portal',
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee('aria-label="Tenant phone navigation"', false)
            ->assertSee(route('tenant.documents.download', $document), false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $document))
            ->assertOk();
    }

    public function test_home_opens_the_tenancy_and_the_next_visit_and_me_keeps_profile_tools(): void
    {
        [$landlord, $accountId, $tenant, $property] = $this->createLet();
        $session = ['current_account_id' => $accountId];
        [$type, $subType] = $this->inspectionType();
        $start = now()->addDays(3)->setTime(10, 0);
        $event = Event::create([
            'account_id' => $accountId,
            'title' => 'Phone inspection',
            'type_id' => $type->id,
            'sub_type_id' => $subType->id,
            'status' => 'Scheduled',
            'visible_to_tenant' => true,
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addHour(),
        ]);
        $event->properties()->attach($property->id);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('data-home-tenancy="1"', false)
            ->assertSee(route('tenant.tenancy'), false)
            ->assertSee('data-home-visit="1"', false)
            ->assertSee(route('tenant.calendar', ['month' => $start->format('Y-m')]), false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.profile'))
            ->assertOk()
            ->assertSee('data-profile-password="1"', false)
            ->assertSee('data-profile-prefs="1"', false)
            ->assertSee('Notification prefs', false)
            ->assertSee(route('tenant.notifications'), false);
    }

    public function test_empty_landlord_and_tenant_screens_name_the_next_action(): void
    {
        [$landlord, $accountId] = $this->createLandlord('empty');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('data-next-action="add-property"', false)
            ->assertSee('data-next-action="add-tenancy"', false)
            ->assertSee('data-next-action="raise-repair"', false)
            ->assertSee('landlord-workspace', false)
            ->assertDontSee('style="background:linear-gradient', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('data-next-action="add-property"', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.all'))
            ->assertOk()
            ->assertSee('data-next-action="add-tenancy"', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('data-next-action=', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.index'))
            ->assertOk()
            ->assertSee('data-next-action="raise-repair"', false);

        $css = file_get_contents(public_path('asset/backend/css/landlord-workspace.css'));
        $this->assertStringContainsString('body.landlord-workspace .lw-hero', $css);
        $this->assertStringContainsString('background: #fff;', $css);

        [$tenantLandlord, $tenantAccount, $tenant] = $this->createLandlord('quiet', true);
        $quiet = ['current_account_id' => $tenantAccount];
        $this->actingAs($tenant)->withSession($quiet)
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('Your landlord has not sent a rent invoice yet.', false);
        $this->actingAs($tenant)->withSession($quiet)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee('Your landlord has not shared a document yet.', false);

        unset($tenantLandlord);
    }

    public function test_landlord_repair_list_titles_use_the_address(): void
    {
        [$landlord, $accountId, $tenant, $property] = $this->createLet('12 Title Road');
        $session = ['current_account_id' => $accountId];
        $category = RepairCategory::query()->orderBy('id')->first()
            ?: RepairCategory::create([
                'name' => 'Phone '.uniqid(),
                'parent_id' => null,
                'level' => 1,
                'description' => 'General',
                'status' => 1,
                'position' => 0,
            ]);
        RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
            'description' => 'Title road drip',
            'status' => 'Open',
            'priority' => 'medium',
            'reference_number' => 'PH-'.uniqid(),
            'created_by' => $landlord->id,
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.index'))
            ->assertOk()
            ->assertSee('data-repair-title="address"', false)
            ->assertSee('12 Title Road', false);
    }

    /**
     * @return array{0: User, 1: int, 2?: User, 3?: Property, 4?: Tenancy}
     */
    private function createLet(string $line = '8 Phone Road'): array
    {
        [$landlord, $accountId, $tenant] = $this->createLandlord('let', true);
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => $line,
            'city' => 'London',
            'postcode' => 'PH1 1PH',
        ]);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 640,
            'deposit' => 0,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'pending',
        ]);

        return [$landlord, $accountId, $tenant, $property, $tenancy];
    }

    /**
     * @return array{0: User, 1: int, 2?: User}
     */
    private function createLandlord(string $suffix, bool $withTenant = false): array
    {
        $landlord = User::create([
            'name' => 'Phone Landlord '.$suffix,
            'email' => 'phone-landlord-'.$suffix.'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $accountId = (int) DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $withTenant) {
            return [$landlord, $accountId];
        }

        $tenant = User::create([
            'name' => 'Phone Tenant '.$suffix,
            'first_name' => 'Phone',
            'email' => 'phone-tenant-'.$suffix.'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole(Role::findOrCreate('Tenant', 'web'));
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$landlord, $accountId, $tenant];
    }

    /**
     * @return array{0: EventType, 1: EventSubType}
     */
    private function inspectionType(): array
    {
        $type = EventType::query()->firstOrCreate(
            ['name' => 'Inspection'],
            ['slug' => 'inspection-phone', 'description' => 'Inspection']
        );
        $subType = EventSubType::query()->firstOrCreate(
            ['event_type_id' => $type->id, 'name' => 'Property Inspection'],
            ['slug' => 'property-inspection-phone', 'description' => 'Inspection']
        );

        return [$type, $subType];
    }
}
