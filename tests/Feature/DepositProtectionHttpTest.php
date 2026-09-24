<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\DocumentType;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepositProtectionHttpTest extends TestCase
{
    public function test_landlord_records_deposit_fields_attaches_prescribed_doc_sees_needs_you_and_reminder(): void
    {
        config(['crm_notifications.enabled' => true]);
        Storage::fake('public');

        [$landlord, $accountId, $property, $tenant] = $this->seedLandlordPropertyAndTenant();
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.store'), [
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => now()->toDateString(),
                'move_out' => now()->addYear()->toDateString(),
                'rent' => 1100,
                'deposit' => 1100,
                'deposit_number' => '5',
                'deposit_scheme' => 'tds',
                'deposit_service' => 'tds_dps_number',
                'tds_dps_number' => 'TDS-998877',
                'deposit_received_at' => now()->subDays(23)->format('Y-m-d\TH:i'),
                'term_months' => 12,
                'term_days' => 0,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect(route('admin.tenancies.all'));

        $tenancy = Tenancy::query()->where('property_id', $property->id)->first();
        $this->assertNotNull($tenancy);
        $this->assertSame('tds', $tenancy->deposit_scheme);
        $this->assertSame('TDS-998877', $tenancy->tds_dps_number);
        $this->assertNotNull($tenancy->deposit_received_at);
        $this->assertTrue($tenancy->depositProtectionNeedsAttention());

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('deposit', false)
            ->assertSee('need you', false);

        $show = $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id));
        $show->assertOk();
        $show->assertSee('data-deposit-protection="1"', false);
        $show->assertSee('Prescribed information document attached', false);
        $show->assertSee('Needs you', false);

        $prescribedType = DocumentType::query()->firstOrCreate(
            ['name' => 'Prescribed Information'],
            ['description' => 'Deposit prescribed information given to the tenant.']
        );

        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => 'prescribed-info',
            'file_name' => 'uploads/all/prescribed-info.pdf',
            'user_id' => $landlord->id,
            'extension' => 'pdf',
            'type' => 'document',
            'file_size' => 48,
        ]);
        Storage::disk('public')->put('uploads/all/prescribed-info.pdf', 'pdf-bytes');

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.documents.save'), [
                'documentable_type' => Tenancy::class,
                'documentable_id' => $tenancy->id,
                'upload_ids' => (string) $upload->id,
                'title' => 'Prescribed information pack',
                'document_type_id' => $prescribedType->id,
                'share_with_tenant' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => (new Tenancy)->getMorphClass(),
            'documentable_id' => $tenancy->id,
            'document_type_id' => $prescribedType->id,
            'title' => 'Prescribed information pack',
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.update', $tenancy->id), [
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => now()->toDateString(),
                'move_out' => now()->addYear()->toDateString(),
                'rent' => 1100,
                'deposit' => 1100,
                'deposit_number' => '5',
                'deposit_scheme' => 'tds',
                'deposit_service' => 'tds_dps_number',
                'tds_dps_number' => 'TDS-998877',
                'deposit_received_at' => now()->subDays(23)->format('Y-m-d\TH:i'),
                'deposit_protected_at' => now()->subDays(20)->format('Y-m-d\TH:i'),
                'prescribed_information_sent_at' => now()->subDays(20)->format('Y-m-d\TH:i'),
                'term_months' => 12,
                'term_days' => 0,
                'user_id' => [$tenant->id],
                'is_main_person' => $tenant->id,
            ])
            ->assertRedirect();

        $tenancy->refresh();
        $this->assertTrue($tenancy->depositProtectionComplete());
        $this->assertFalse($tenancy->depositProtectionNeedsAttention());

        Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
            'deposit' => 900,
            'deposit_scheme' => 'dps',
            'tds_dps_number' => 'DPS-1',
            'deposit_received_at' => now()->subDays(23),
            'move_in' => now()->toDateString(),
        ]);

        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => now()->toIso8601String(),
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::TenancyDepositDue->value,
        ]);
    }

    /**
     * @return array{0: User, 1: int, 2: Property, 3: User}
     */
    private function seedLandlordPropertyAndTenant(): array
    {
        foreach (['create tenancies', 'edit tenancies', 'delete tenancies', 'view tenancies'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create tenancies', 'edit tenancies', 'delete tenancies', 'view tenancies']);

        $landlord = User::create([
            'name' => 'Deposit Landlord '.uniqid(),
            'email' => 'deposit-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $landlord->assignRole($role);

        $tenantRole = Role::findOrCreate('Tenant', 'web');
        $tenant = User::create([
            'name' => 'Deposit Tenant '.uniqid(),
            'email' => 'deposit-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole($tenantRole);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$landlord, $tenant] as $user) {
            DB::table('account_users')->insert([
                'account_id' => $accountId,
                'user_id' => $user->id,
                'member_type' => $user->id === $landlord->id ? 'owner' : 'tenant',
                'access_level' => 'full',
                'can_login' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '12 Deposit Way',
            'city' => 'London',
            'postcode' => 'E1 1AA',
            'letting_current_status' => 'available',
        ]);

        return [$landlord, $accountId, $property, $tenant];
    }
}
