<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantPortalHttpTest extends TestCase
{
    public function test_tenant_home_shows_portal_dashboard(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Tenant portal', false)
            ->assertSee('Outstanding rent', false)
            ->assertSee('Upcoming appointments', false)
            ->assertSee('Report an issue', false)
            ->assertSee(route('tenant.maintenance'), false)
            ->assertDontSee(route('admin.property_repairs.create'), false);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.calendar'))
            ->assertOk()
            ->assertSee('Appointments', false);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertSee('Repair requests', false);
    }

    public function test_tenant_agency_repair_form_redirects_to_portal_maintenance(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.property_repairs.create'))
            ->assertRedirect(route('tenant.maintenance'));
    }

    public function test_landlord_cannot_open_tenant_tenancy_page(): void
    {
        [$user, $accountId] = $this->createPortalUser('Landlord', 'owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.tenancy'))
            ->assertForbidden();
    }

    public function test_landlord_cannot_open_tenant_calendar_page(): void
    {
        [$user, $accountId] = $this->createPortalUser('Landlord', 'owner');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.calendar'))
            ->assertForbidden();
    }

    public function test_tenant_cannot_open_staff_calendar(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.events.calendar'))
            ->assertForbidden();
    }

    public function test_tenant_cannot_open_saas_or_billing(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.saas.accounts.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.saas.plans.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('backend.billing.index'))
            ->assertForbidden();

        $this->actingAs($user)->withSession($session)
            ->get(route('admin.finance.index'))
            ->assertForbidden();
    }

    public function test_tenant_home_hides_staff_menu(): void
    {
        [$user, $accountId] = $this->createPortalUser('Tenant', 'tenant');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('My Tenancy', false)
            ->assertDontSee('View Active Properties', false)
            ->assertDontSee('View Active Tenancies', false)
            ->assertDontSee('Billing &amp; Plan', false)
            ->assertDontSee('Sales Offer', false);
    }

    public function test_tenant_does_not_see_another_tenants_home(): void
    {
        [$landlord, $accountId] = $this->createPortalUser('Landlord', 'owner');
        [$tenantA] = $this->createPortalUser('Tenant', 'tenant');
        [$tenantB] = $this->createPortalUser('Tenant', 'tenant');

        \DB::table('account_users')->insert([
            [
                'account_id' => $accountId,
                'user_id' => $tenantA->id,
                'member_type' => 'tenant',
                'access_level' => 'view',
                'can_login' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $accountId,
                'user_id' => $tenantB->id,
                'member_type' => 'tenant',
                'access_level' => 'view',
                'can_login' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $propertyA = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '12 Tenant A Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);
        $propertyB = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '99 Secret Tenant B Lane',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);

        $tenancyA = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $propertyA->id,
            'status' => 'Active',
            'rent' => 1000,
        ]);
        $tenancyB = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $propertyB->id,
            'status' => 'Active',
            'rent' => 1200,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancyA->id,
            'user_id' => $tenantA->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancyB->id,
            'user_id' => $tenantB->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        $this->actingAs($tenantA)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('12 Tenant A Street', false)
            ->assertDontSee('99 Secret Tenant B Lane', false);
    }

    public function test_tenant_repair_request_saves_medium_priority(): void
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
            'line_1' => '14 Repair Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
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

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.maintenance.store'), [
                'property_id' => $property->id,
                'description' => 'Kitchen tap is dripping',
                'priority' => 'medium',
            ])
            ->assertRedirect(route('tenant.maintenance'));

        $this->assertDatabaseHas('repair_issues', [
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'description' => 'Kitchen tap is dripping',
            'priority' => 'medium',
        ]);
    }

    public function test_tenant_can_download_shared_document_and_not_private_or_foreign_files(): void
    {
        Storage::fake('public');

        [$landlord, $accountId] = $this->createPortalUser('Landlord', 'owner');
        [$tenant] = $this->createPortalUser('Tenant', 'tenant');
        [$otherLandlord, $otherAccount] = $this->createPortalUser('Landlord', 'owner');

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
            'line_1' => '20 Shared File Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
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

        $shared = $this->createPropertyDocument($accountId, $landlord->id, $property->id, 'portal', 'gas-safety.txt');
        $private = $this->createPropertyDocument($accountId, $landlord->id, $property->id, 'private', 'internal.txt');

        $foreignProperty = Property::create([
            'account_id' => $otherAccount,
            'created_by' => $otherLandlord->id,
            'line_1' => '88 Other Account Street',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);
        $foreign = $this->createPropertyDocument($otherAccount, $otherLandlord->id, $foreignProperty->id, 'portal', 'foreign.txt');

        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee(route('tenant.documents.download', $shared), false)
            ->assertDontSee(route('tenant.documents.download', $private), false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $shared))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $private))
            ->assertNotFound();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $foreign))
            ->assertNotFound();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.documents.share', $private), ['share_with_tenant' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id' => $private->id,
            'visibility' => 'portal',
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $private))
            ->assertOk();
    }

    private function createPropertyDocument(int $accountId, int $userId, int $propertyId, string $visibility, string $filename): Document
    {
        $path = 'uploads/all/'.$filename;
        Storage::disk('public')->put($path, 'certificate-body');

        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => $filename,
            'file_name' => $path,
            'user_id' => $userId,
            'extension' => 'txt',
            'type' => 'document',
            'file_size' => 16,
        ]);

        return Document::create([
            'account_id' => $accountId,
            'documentable_type' => Property::class,
            'documentable_id' => $propertyId,
            'upload_ids' => (string) $upload->id,
            'visibility' => $visibility,
            'created_by' => $userId,
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPortalUser(string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' Portal',
            'first_name' => $roleName,
            'email' => 'tenant-portal-'.uniqid().'@resisquare.test',
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
