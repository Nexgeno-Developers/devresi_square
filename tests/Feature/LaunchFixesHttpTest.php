<?php

namespace Tests\Feature;

use App\Models\ComplianceRecord;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaunchFixesHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['contractor_marketplace.geocode.enabled' => false]);
    }

    public function test_public_migrate_is_closed_and_the_new_migration_does_not_seed(): void
    {
        $this->get('/migrate')->assertNotFound();

        $migration = file_get_contents(database_path('migrations/2026_10_15_000004_create_repair_desk_requests_table.php'));
        $this->assertStringNotContainsString('db:seed', $migration);
        $this->assertStringNotContainsString('Staging', $migration);
        $this->assertStringNotContainsString('Schema::dropIfExists(\'users\'', $migration);
        $this->assertStringContainsString('Schema::dropIfExists(\'repair_desk_requests\')', $migration);
    }

    public function test_people_page_lists_tenants_only(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $contractor = User::create([
            'name' => 'Carl Contractor',
            'email' => 'carl-people-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $contractor->assignRole(Role::findOrCreate('Contractor', 'web'));
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $contractor->id,
            'member_type' => 'contractor',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.people.index'))
            ->assertOk()
            ->assertDontSee($contractor->email, false)
            ->assertDontSee('Resend invite', false);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.revoke', $contractor))
            ->assertNotFound();
    }

    public function test_landlord_can_send_a_repair_to_the_resisquare_desk_without_touching_offers(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.desk.store', $repair), ['note' => 'Boiler is off'])
            ->assertRedirect(route('admin.property_repairs.show', $repair).'#contractors');

        $desk = RepairDeskRequest::query()->where('repair_issue_id', $repair->id)->first();
        $this->assertNotNull($desk);
        $this->assertSame(RepairDeskRequest::REQUESTED, $desk->status);
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());
        $this->assertNull($repair->fresh()->awarded_offer_id);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.marketplace.find', $repair))
            ->assertRedirect(route('admin.property_repairs.show', $repair).'#contractors');

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair))
            ->assertOk()
            ->assertSee('Take it back', false)
            ->assertSee('Boiler is off', false)
            ->assertDontSee('Find a contractor', false);

        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->get(route('backend.saas.repair_desk.index'))
            ->assertOk()
            ->assertSee('Boiler is off', false)
            ->assertSee('Repair desk', false);

        $this->actingAs($admin)->post(route('backend.saas.repair_desk.update', $desk), [
            'status' => RepairDeskRequest::IN_PROGRESS,
            'assigned_to' => $admin->id,
            'team_note' => 'Engineer booked',
        ])->assertRedirect(route('backend.saas.repair_desk.index'));

        $this->assertSame(RepairDeskRequest::IN_PROGRESS, $desk->fresh()->status);
        $this->assertSame($admin->id, (int) $desk->fresh()->assigned_to);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.desk.withdraw', $repair))
            ->assertSessionHas('error');
        $this->assertSame(RepairDeskRequest::IN_PROGRESS, $desk->fresh()->status);

        $this->actingAs($admin)->post(route('backend.saas.repair_desk.update', $desk), [
            'status' => RepairDeskRequest::RETURNED,
            'assigned_to' => $admin->id,
            'team_note' => 'Returned',
        ])->assertRedirect();

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.marketplace.find', $repair))
            ->assertOk()
            ->assertSee('Find a contractor', false);
    }

    public function test_admin_logout_returns_the_branded_sign_in(): void
    {
        [$landlord] = $this->createLandlord();

        $this->actingAs($landlord)
            ->get(route('backend.logout'))
            ->assertRedirect(route('login'));
    }

    public function test_certificate_banner_names_missing_certificates(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'prop_name' => 'Harbour House',
            'line_1' => '1 Harbour Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);

        $response = $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $response->assertOk();
        if (count(ComplianceRecord::certificateGapsForAccount($accountId)) > 0) {
            $response->assertSee('missing, expired, or due within 60 days', false);
            $response->assertDontSee('expired or due within 60 days', false);
        }
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $user = User::create([
            'name' => 'Launch Landlord',
            'email' => 'launch-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));
        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'account_name' => 'Launch Landlord Account',
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

    private function repair(User $landlord, int $accountId): RepairIssue
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'prop_name' => 'Harbour House',
            'line_1' => '1 Harbour Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);
        $category = RepairCategory::create([
            'name' => 'General '.uniqid(),
            'level' => 0,
            'description' => 'Test',
            'status' => 0,
        ]);

        $tenant = User::create([
            'name' => 'Desk Tenant',
            'email' => 'desk-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['area' => 'kitchen']),
            'description' => 'No hot water',
            'tenant_id' => $tenant->id,
            'priority' => 'medium',
            'status' => 'Pending',
            'sub_status' => 'Pending',
            'reference_number' => 'REP-'.uniqid(),
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::create([
            'name' => 'Desk Admin',
            'email' => 'desk-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));

        return $user;
    }
}
