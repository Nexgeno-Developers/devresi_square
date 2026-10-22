<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\ComplianceRecord;
use App\Models\NotificationLog;
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

    public function test_migrate_url_is_super_admin_only_and_does_not_seed(): void
    {
        $this->get('/migrate')->assertRedirect(route('login'));
        $this->post('/migrate', ['action' => 'run', 'seed' => '1'])->assertRedirect(route('login'));

        [$landlord] = $this->createLandlord();
        $this->actingAs($landlord)->get('/migrate')->assertForbidden();
        $this->actingAs($landlord)->post('/migrate', ['action' => 'run', 'seed' => '1'])->assertForbidden();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/migrate')
            ->assertOk()
            ->assertSee('Migrations not run yet')
            ->assertDontSee('Migrate token');

        $this->actingAs($admin)->post('/migrate', ['action' => 'preview', 'seed' => '1'])
            ->assertOk()
            ->assertDontSee('Database seeding');

        $migration = file_get_contents(database_path('migrations/2026_10_15_000004_create_repair_desk_requests_table.php'));
        $this->assertStringNotContainsString('db:seed', $migration);
        $this->assertStringNotContainsString('Staging', $migration);
        $this->assertStringNotContainsString('Schema::dropIfExists(\'users\'', $migration);
        $this->assertStringContainsString('Schema::dropIfExists(\'repair_desk_requests\')', $migration);

        $guard = app(\App\Services\Schema\WebMigrator::class);
        $this->assertNull($guard->rejectionReason(database_path('migrations/2026_10_15_000004_create_repair_desk_requests_table.php')));

        $probe = database_path('migrations/2099_01_01_000000_web_migrate_guard_probe.php');
        file_put_contents($probe, <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::dropIfExists('users'); }
    public function down(): void {}
};
PHP);
        try {
            $this->assertSame('drops a table', $guard->rejectionReason($probe));
        } finally {
            @unlink($probe);
        }
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
        ])->assertRedirect(route('backend.saas.repair_desk.show', $desk));

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

    public function test_completing_a_resisquare_desk_request_updates_the_repair_and_notifies(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.desk.store', $repair), ['note' => 'Please send someone'])
            ->assertRedirect();

        $desk = RepairDeskRequest::query()->where('repair_issue_id', $repair->id)->firstOrFail();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('backend.saas.repair_desk.update', $desk), [
            'status' => RepairDeskRequest::COMPLETED,
            'assigned_to' => $admin->id,
            'team_note' => 'Boiler replaced',
        ])->assertRedirect(route('backend.saas.repair_desk.show', $desk));

        $repair->refresh();
        $this->assertSame('Work Completed', $repair->status);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair))
            ->assertOk()
            ->assertSee('The Resisquare team marked this repair complete.', false)
            ->assertSee('Boiler replaced', false);

        foreach ([$landlord->id, (int) $repair->tenant_id] as $userId) {
            $this->assertTrue(NotificationLog::query()
                ->where('identifier', CrmNotificationEvent::RepairJobCompleted->value)
                ->where('subject_id', $repair->id)
                ->where('notifiable_id', $userId)
                ->where('channel', 'email')
                ->exists(), 'Missing completion email for user '.$userId);
        }
    }

    public function test_resisquare_desk_job_runs_from_take_to_complete(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $session = ['current_account_id' => $accountId];
        $admin = $this->superAdmin();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.property_repairs.desk.store', $repair), ['note' => 'No heating'])
            ->assertRedirect();

        $desk = RepairDeskRequest::query()->where('repair_issue_id', $repair->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('backend.saas.repair_desk.show', $desk))
            ->assertOk()
            ->assertSee('Take this repair', false)
            ->assertSee('No hot water', false)
            ->assertSee('No heating', false);

        $this->actingAs($admin)
            ->post(route('backend.saas.repair_desk.take', $desk), ['note' => 'Engineer booked for Friday'])
            ->assertRedirect(route('backend.saas.repair_desk.show', $desk));

        $repair->refresh();
        $this->assertSame('Under Process', $repair->status);
        $this->assertSame(RepairDeskRequest::IN_PROGRESS, $desk->fresh()->status);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.property_repairs.show', $repair))
            ->assertOk()
            ->assertSee('Engineer booked for Friday', false)
            ->assertDontSee('Find a contractor', false);

        $this->actingAs($admin)
            ->post(route('backend.saas.repair_desk.progress', $desk), ['note' => 'Parts are on the way'])
            ->assertRedirect(route('backend.saas.repair_desk.show', $desk));

        $this->actingAs($admin)
            ->post(route('backend.saas.repair_desk.complete', $desk), ['note' => 'Heating restored'])
            ->assertRedirect(route('backend.saas.repair_desk.show', $desk));

        $repair->refresh();
        $this->assertSame('Work Completed', $repair->status);
        $this->assertSame(RepairDeskRequest::COMPLETED, $desk->fresh()->status);

        $story = app(\App\Services\Portal\TenantRepairStory::class)->for($repair);
        $text = collect($story['activity'])->pluck('text')->implode(' ');
        $this->assertStringContainsString('asked Resisquare', $text);
        $this->assertStringContainsString('Parts are on the way', $text);
        $this->assertStringContainsString('Heating restored', $text);

        foreach ([CrmNotificationEvent::RepairJobProgress, CrmNotificationEvent::RepairJobCompleted] as $event) {
            $this->assertTrue(NotificationLog::query()
                ->where('identifier', $event->value)
                ->where('subject_id', $repair->id)
                ->where('notifiable_id', $repair->tenant_id)
                ->where('channel', 'email')
                ->exists());
            $this->assertTrue(NotificationLog::query()
                ->where('identifier', $event->value)
                ->where('subject_id', $repair->id)
                ->where('notifiable_id', $landlord->id)
                ->where('channel', 'email')
                ->exists());
        }
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
