<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Portal\TenantPortalService;
use App\Services\PropertyArchiveService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 14: archiving a property must not leave Active tenancies / issued invoices.
 */
class PropertyArchiveLifecycleHttpTest extends TestCase
{
    public function test_archive_closes_active_tenancy_and_voids_unpaid_invoice(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        Permission::findOrCreate('delete properties', 'web');
        $landlord->givePermissionTo('delete properties');

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '99 Archive Close',
            'city' => 'London',
            'postcode' => 'E9 9AA',
        ]);

        $tenant = User::create([
            'name' => 'Archive Tenant',
            'email' => 'archive-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1200,
            'move_in' => now()->toDateString(),
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => 1,
            'can_login' => 1,
        ]);

        $invoice = RentInvoice::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'invoice_no' => 'RENT-ARCH-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 1200,
            'balance' => 1200,
            'status' => RentInvoice::STATUS_ISSUED,
        ]);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.properties.delete', ['id' => $property->id]))
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertSoftDeleted('properties', ['id' => $property->id]);

        $tenancy->refresh();
        $this->assertSame('Archived', $tenancy->status);

        $invoice->refresh();
        $this->assertSame(RentInvoice::STATUS_VOID, $invoice->status);

        $activeCount = Tenancy::query()
            ->where('account_id', $accountId)
            ->where('status', 'Active')
            ->whereHas('property')
            ->count();
        $this->assertSame(0, $activeCount);

        $portalTenancies = app(TenantPortalService::class)->tenanciesFor($tenant, $accountId);
        $this->assertTrue($portalTenancies->isEmpty());
    }

    public function test_archive_service_is_idempotent_on_already_trashed_property(): void
    {
        [$landlord, $accountId] = $this->createLandlord();

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '100 Archive Way',
            'city' => 'London',
            'postcode' => 'E9 9BB',
        ]);

        $property->deleted_by = $landlord->id;
        $property->save();
        $property->delete();

        app(PropertyArchiveService::class)->archive($property->fresh(), $landlord->id);

        $this->assertTrue($property->fresh()->trashed());
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Archive Landlord '.uniqid(),
            'email' => 'archive-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
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
