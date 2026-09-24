<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenancyCorrectionRequest;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenancyDetailsConfirmationHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['crm_notifications.enabled' => false]);
    }

    public function test_invited_tenant_sees_confirmation_popup_with_tenancy_details(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Check these tenancy details', false)
            ->assertSee('£1,250.00', false)
            ->assertSee('1 October Street', false)
            ->assertSee('Looks correct', false)
            ->assertSee('Something is wrong', false);
    }

    public function test_tenant_can_confirm_tenancy_details(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.confirm'), [
                'tenancy_id' => $tenancy->id,
            ])
            ->assertRedirect(route('tenant.tenancy'));

        $this->assertDatabaseHas('tenant_members', [
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'details_status' => 'confirmed',
        ]);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('Confirmed', false);

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('Check these tenancy details', false);
    }

    public function test_tenant_can_raise_a_correction_request(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Rent should be 1100 and move-in is 1 October.',
                'fields' => ['rent', 'move_in'],
                'suggested' => [
                    'rent' => '1100',
                    'move_in' => '2026-10-01',
                ],
            ])
            ->assertRedirect(route('tenant.tenancy'));

        $this->assertDatabaseHas('tenancy_correction_requests', [
            'tenancy_id' => $tenancy->id,
            'requested_by' => $tenant->id,
            'status' => 'pending',
            'message' => 'Rent should be 1100 and move-in is 1 October.',
        ]);

        $this->assertDatabaseHas('tenant_members', [
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'details_status' => 'correction_requested',
        ]);

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Waiting for your landlord', false)
            ->assertDontSee('Looks correct', false);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('Correction from', false)
            ->assertSee('Rent should be 1100', false)
            ->assertSee('Approve and update tenancy', false);
    }

    public function test_landlord_can_approve_correction_and_tenant_must_reconfirm(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Rent is wrong.',
                'fields' => ['rent'],
                'suggested' => ['rent' => '1100'],
            ])
            ->assertRedirect(route('tenant.tenancy'));

        $request = TenancyCorrectionRequest::query()->where('tenancy_id', $tenancy->id)->firstOrFail();

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.correction-requests.approve', [$tenancy, $request]), [
                'rent' => '1100.00',
                'landlord_note' => 'Updated to the agreed rent.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenancies', [
            'id' => $tenancy->id,
            'rent' => 1100,
        ]);
        $this->assertDatabaseHas('tenancy_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('tenant_members', [
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'details_status' => 'pending',
        ]);

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Check these tenancy details', false)
            ->assertSee('£1,100.00', false);
    }

    public function test_landlord_can_reject_correction_and_tenant_is_asked_again(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Deposit looks high.',
                'fields' => ['deposit'],
                'suggested' => ['deposit' => '500'],
            ]);

        $request = TenancyCorrectionRequest::query()->where('tenancy_id', $tenancy->id)->firstOrFail();

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.correction-requests.reject', [$tenancy, $request]), [
                'landlord_note' => 'Deposit is correct as agreed.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenancy_correction_requests', [
            'id' => $request->id,
            'status' => 'rejected',
        ]);
        $this->assertSame(1500.0, (float) $tenancy->fresh()->deposit);

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Check these tenancy details', false)
            ->assertSee('Deposit is correct as agreed.', false);
    }

    public function test_tenant_cannot_confirm_another_tenants_let(): void
    {
        [, $accountId, $tenant] = $this->createLet();
        [, , $otherTenant, $otherTenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.confirm'), [
                'tenancy_id' => $otherTenancy->id,
            ])
            ->assertSessionHasErrors('tenancy_id');

        $this->assertDatabaseMissing('tenant_members', [
            'tenancy_id' => $otherTenancy->id,
            'user_id' => $otherTenant->id,
            'details_status' => 'confirmed',
        ]);
    }

    public function test_landlord_dashboard_shows_pending_correction_banner(): void
    {
        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Move-in date is wrong.',
                'fields' => ['move_in'],
                'suggested' => ['move_in' => '2026-10-01'],
            ]);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('tenancy correction request waiting', false)
            ->assertSee(route('admin.tenancies.show', $tenancy->id), false);
    }

    public function test_correction_lifecycle_dispatches_crm_notifications(): void
    {
        config(['crm_notifications.enabled' => true]);
        Queue::fake();

        [$landlord, $accountId, $tenant, $tenancy] = $this->createLet();

        $this->actingAs($tenant)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.correction'), [
                'tenancy_id' => $tenancy->id,
                'message' => 'Rent should be lower.',
                'fields' => ['rent'],
                'suggested' => ['rent' => '1100'],
            ])
            ->assertRedirect(route('tenant.tenancy'));

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::TenancyDetailsCorrectionRequested->value,
            'notifiable_id' => $landlord->id,
        ]);

        $request = TenancyCorrectionRequest::query()->where('tenancy_id', $tenancy->id)->firstOrFail();

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.correction-requests.approve', [$tenancy, $request]), [
                'rent' => '1100.00',
                'landlord_note' => 'Agreed.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::TenancyDetailsCorrectionApproved->value,
            'notifiable_id' => $tenant->id,
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    /**
     * @return array{0: User, 1: int, 2: User, 3: Tenancy}
     */
    private function createLet(): array
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
            'line_1' => '1 October Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1250,
            'deposit' => 1500,
            'frequency' => 'Monthly',
            'move_in' => '2026-09-01',
            'term_months' => 12,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
            'details_status' => 'pending',
        ]);

        return [$landlord, $accountId, $tenant, $tenancy];
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPortalUser(string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' Confirm',
            'first_name' => $roleName,
            'email' => 'tenancy-confirm-'.uniqid().'@resisquare.test',
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
