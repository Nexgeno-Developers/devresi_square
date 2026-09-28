<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventSubType;
use App\Models\EventType;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Documents\DocumentShareNotifier;
use App\Services\Finance\RentFinanceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NoticesHandoffHttpTest extends TestCase
{
    public function test_turning_off_rent_email_keeps_the_in_app_notice_and_the_next_one_emails_again(): void
    {
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();
        $session = ['current_account_id' => $accountId];
        $finance = app(RentFinanceService::class);

        $first = $this->issue($finance, $accountId, $tenancy, $tenant, '2026-09-01');
        $this->assertNoticeChannels($first, $tenant, ['email', 'system']);

        $this->actingAs($tenant)->withSession($session)
            ->put(route('tenant.notifications.update'), [
                'preferences' => [[
                    'event_key' => CrmNotificationEvent::FinanceInvoiceIssued->value,
                    'email_enabled' => 0,
                    'in_app_enabled' => 1,
                ]],
            ])
            ->assertRedirect(route('tenant.notifications'));

        $second = $this->issue($finance, $accountId, $tenancy, $tenant, '2026-10-01');
        $this->assertNoticeChannels($second, $tenant, ['system']);

        $this->actingAs($tenant)->withSession($session)
            ->put(route('tenant.notifications.update'), [
                'preferences' => [[
                    'event_key' => CrmNotificationEvent::FinanceInvoiceIssued->value,
                    'email_enabled' => 1,
                    'in_app_enabled' => 1,
                ]],
            ])
            ->assertRedirect(route('tenant.notifications'));

        $third = $this->issue($finance, $accountId, $tenancy, $tenant, '2026-11-01');
        $this->assertNoticeChannels($third, $tenant, ['email', 'system']);
    }

    public function test_tenant_notice_links_open_the_portal_pages(): void
    {
        config(['crm_notifications.enabled' => true]);
        Storage::fake('public');

        [$landlord, $accountId, $tenant, $tenancy, $property] = $this->createLet();
        $session = ['current_account_id' => $accountId];

        $invoice = $this->issue(app(RentFinanceService::class), $accountId, $tenancy, $tenant, now()->toDateString());
        $this->assertTenantLink($invoice, $tenant, CrmNotificationEvent::FinanceInvoiceIssued, route('tenant.rent.show', $invoice));

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $property->id,
                'description' => 'Notice tap leak',
                'priority' => 'medium',
                'photo' => UploadedFile::fake()->image('leak.jpg'),
            ])
            ->assertRedirect();

        $repair = \App\Models\RepairIssue::query()->forAccount($accountId)->where('description', 'Notice tap leak')->firstOrFail();
        $this->assertTenantLink($repair, $tenant, CrmNotificationEvent::RepairReported, route('tenant.maintenance'));

        $document = Document::create([
            'account_id' => $accountId,
            'documentable_id' => $property->id,
            'documentable_type' => $property->getMorphClass(),
            'title' => 'Shared gas certificate',
            'visibility' => 'portal',
        ]);
        app(DocumentShareNotifier::class)->shared($document);
        $this->assertTenantLink($document, $tenant, CrmNotificationEvent::DocumentShared, route('tenant.documents'));

        [$type, $subType] = $this->inspectionType();
        $start = now()->addMinutes(30);
        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('backend.events.store'), [
                'title' => 'Notice visit',
                'type_id' => $type->id,
                'sub_type_id' => $subType->id,
                'status' => 'Scheduled',
                'start_datetime' => $start->format('Y-m-d H:i:s'),
                'end_datetime' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
                'property_ids' => [$property->id],
                'invite_ids' => [$tenant->id],
                'reminders' => [
                    ['minutes_before' => 60, 'channel' => 'email'],
                ],
            ])
            ->assertOk();

        Artisan::call('events:send-reminders');
        $event = Event::query()->forAccount($accountId)->where('title', 'Notice visit')->firstOrFail();
        $this->assertTenantLink($event, $tenant, CrmNotificationEvent::AppointmentReminder, route('tenant.calendar'));

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.profile'))
            ->assertOk()
            ->assertSee('data-tenant-notices="profile"', false)
            ->assertSee('Shared gas certificate', false)
            ->assertSee(route('tenant.documents'), false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.notifications'))
            ->assertOk()
            ->assertSee('data-tenant-notices="list"', false)
            ->assertSee(route('tenant.rent.show', $invoice), false)
            ->assertDontSee('&lt;p&gt;', false)
            ->assertSee('is due on', false)
            ->assertSee(route('tenant.maintenance'), false)
            ->assertSee(route('tenant.calendar'), false);
    }

    public function test_landlord_home_counts_match_the_screens_and_disappear_at_zero(): void
    {
        config(['crm_notifications.enabled' => false]);

        [$landlord, $accountId, $tenant, $tenancy, $property] = $this->createLet(['deposit' => 1200]);
        $session = ['current_account_id' => $accountId];

        foreach (['gas' => 'Gas', 'epc' => 'EPC', 'eicr' => 'EICR'] as $alias => $name) {
            ComplianceType::query()->firstOrCreate(
                ['alias' => $alias],
                ['name' => $name, 'description' => $name]
            );
        }

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Rent looks high.',
                'fields' => ['rent'],
                'suggested' => ['rent' => '900'],
            ])
            ->assertRedirect();

        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1200,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $home = $this->actingAs($landlord)->withSession($session)->get(route('backend.dashboard'));
        $home->assertOk()
            ->assertSee('data-alert-corrections="1"', false)
            ->assertSee('data-alert-arrears="1"', false)
            ->assertSee('data-alert-certificates="3"', false)
            ->assertSee('data-alert-deposits="1"', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('data-pending-corrections="1"', false)
            ->assertSee('data-deposit-protection="1"', false)
            ->assertSee('Needs you', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertSee('data-overdue-count="1"', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.compliance.index'))
            ->assertOk()
            ->assertSee('data-certificate-gaps="3"', false);

        $request = \App\Models\TenancyCorrectionRequest::query()->where('tenancy_id', $tenancy->id)->firstOrFail();
        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.correction-requests.reject', [$tenancy, $request]), [
                'landlord_note' => 'Rent stands.',
            ])
            ->assertRedirect();

        $invoice->forceFill(['due_date' => now()->addDays(10)->toDateString()])->save();
        $tenancy->forceFill(['deposit' => 0, 'deposit_received_at' => null])->save();

        foreach (ComplianceType::query()->whereIn('alias', ['gas', 'epc', 'eicr'])->get() as $type) {
            ComplianceRecord::create([
                'property_id' => $property->id,
                'compliance_type_id' => $type->id,
                'issued_date' => now()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
                'status' => 'valid',
            ]);
        }

        $cleared = $this->actingAs($landlord)->withSession($session)->get(route('backend.dashboard'));
        $cleared->assertOk()
            ->assertDontSee('data-alert-corrections=', false)
            ->assertDontSee('data-alert-arrears=', false)
            ->assertDontSee('data-alert-certificates=', false)
            ->assertDontSee('data-alert-deposits=', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.compliance.index'))
            ->assertOk()
            ->assertSee('data-certificate-gaps="0"', false);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.finance.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertDontSee('data-overdue-count=', false);
    }

    private function issue(RentFinanceService $finance, int $accountId, Tenancy $tenancy, User $tenant, string $date)
    {
        return $finance->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1000,
            'issue_date' => $date,
            'due_date' => $date,
        ]);
    }

    /**
     * @param  list<string>  $channels
     */
    private function assertNoticeChannels(object $subject, User $tenant, array $channels): void
    {
        $found = NotificationLog::query()
            ->where('subject_id', $subject->id)
            ->where('identifier', CrmNotificationEvent::FinanceInvoiceIssued->value)
            ->where('notifiable_id', $tenant->id)
            ->pluck('channel')
            ->sort()
            ->values()
            ->all();

        sort($channels);
        $this->assertSame($channels, $found);
    }

    private function assertTenantLink(object $subject, User $tenant, CrmNotificationEvent $event, string $url): void
    {
        $log = NotificationLog::query()
            ->where('subject_id', $subject->id)
            ->where('identifier', $event->value)
            ->where('notifiable_id', $tenant->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($url, $log->payload['action_url'] ?? null);
        $this->assertStringNotContainsString('/admin/finance/', (string) ($log->payload['action_url'] ?? ''));
        $this->assertStringNotContainsString('/admin/calendar', (string) ($log->payload['action_url'] ?? ''));
        $this->assertStringNotContainsString('/property-repairs/', (string) ($log->payload['action_url'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: int, 2: User, 3: Tenancy, 4: Property}
     */
    private function createLet(array $overrides = []): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $landlord = User::create([
            'name' => 'Notice Landlord',
            'email' => 'notice-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $landlord->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
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

        $tenantRole = Role::findOrCreate('Tenant', 'web');
        $tenant = User::create([
            'name' => 'Notice Tenant',
            'first_name' => 'Notice',
            'email' => 'notice-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole($tenantRole);

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

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '9 Notice Road',
            'city' => 'London',
            'postcode' => 'N9 9AA',
        ]);

        $tenancy = Tenancy::create(array_merge([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'deposit' => 0,
            'frequency' => 'Monthly',
            'move_in' => now()->subMonth()->toDateString(),
        ], $overrides));

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'pending',
        ]);

        return [$landlord, $accountId, $tenant, $tenancy, $property];
    }

    /**
     * @return array{0: EventType, 1: EventSubType}
     */
    private function inspectionType(): array
    {
        $type = EventType::query()->firstOrCreate(
            ['name' => 'Inspection'],
            ['slug' => 'inspection-notices', 'description' => 'Inspection']
        );
        $subType = EventSubType::query()->firstOrCreate(
            ['event_type_id' => $type->id, 'name' => 'Property Inspection'],
            ['slug' => 'property-inspection-notices', 'description' => 'Inspection']
        );

        return [$type, $subType];
    }
}
