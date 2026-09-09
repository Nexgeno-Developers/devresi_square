<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\OwnerGroup;
use App\Models\Property;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceIsolationHttpTest extends TestCase
{
    public function test_landlord_cannot_open_another_accounts_document_or_repair(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();

        $propertyB = Property::create([
            'account_id' => $accountB,
            'created_by' => $landlordB->id,
            'line_1' => '88 Foreign Document Street',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);

        $documentAttrs = [
            'documentable_type' => Property::class,
            'documentable_id' => $propertyB->id,
            'upload_ids' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('documents', 'account_id')) {
            $documentAttrs['account_id'] = $accountB;
        }
        $documentId = \DB::table('documents')->insertGetId($documentAttrs);

        $category = \App\Models\RepairCategory::query()->orderBy('id')->first()
            ?: \App\Models\RepairCategory::create([
                'name' => 'Isolation test',
                'parent_id' => null,
                'level' => 1,
                'description' => 'Test',
                'status' => 1,
                'position' => 0,
            ]);

        $repair = RepairIssue::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['Isolation test']),
            'description' => 'Secret boiler leak',
            'priority' => 'medium',
            'status' => 'Pending',
            'created_by' => $landlordB->id,
        ]);

        $this->actingAs($landlordA)
            ->withSession(['current_account_id' => $accountA])
            ->get(route('admin.documents.show', ['id' => $documentId]))
            ->assertForbidden();

        $this->actingAs($landlordA)
            ->withSession(['current_account_id' => $accountA])
            ->get(route('admin.property_repairs.show', ['id' => $repair->id]))
            ->assertForbidden();
    }

    public function test_tenant_cannot_raise_repair_on_another_tenants_home(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createMembershipOnAccount($accountId, 'Tenant', 'tenant');

        $ownProperty = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '10 Own Home Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);
        $otherProperty = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '99 Other Home Lane',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $ownProperty->id,
            'status' => 'Active',
            'rent' => 1000,
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
                'property_id' => $otherProperty->id,
                'description' => 'Trying to report on someone else home',
            ])
            ->assertSessionHasErrors('property_id');
    }

    public function test_landlord_cannot_open_another_accounts_owner_group(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();

        $propertyB = Property::create([
            'account_id' => $accountB,
            'created_by' => $landlordB->id,
            'line_1' => '7 Owner Group Street',
            'city' => 'London',
            'postcode' => 'N1 9GU',
        ]);

        $group = OwnerGroup::create([
            'property_id' => $propertyB->id,
            'purchased_date' => now()->toDateString(),
            'status' => 'active',
            'added_by' => $landlordB->id,
        ]);

        $this->actingAs($landlordA)
            ->withSession(['current_account_id' => $accountA])
            ->get(route('admin.owner-groups.show', ['ownerGroup' => $group->id]))
            ->assertNotFound();
    }

    public function test_landlord_can_invite_and_revoke_tenant_from_portal_access_page(): void
    {
        Mail::fake();

        [$landlord, $accountId] = $this->createLandlord();

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '3 Invite Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 950,
        ]);

        $email = 'invite-page-'.uniqid().'@resisquare.test';

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.invite'), [
                'name' => 'Invited Tenant',
                'email' => $email,
                'tenancy_id' => $tenancy->id,
            ])
            ->assertRedirect(route('admin.portal-access.index'));

        $tenant = User::query()->where('email', $email)->first();
        $this->assertNotNull($tenant);

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($email) {
            $content = (string) ($mail->array['content'] ?? '');

            return $mail->hasTo($email)
                && str_contains($content, 'email='.urlencode($email));
        });
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $email,
        ]);

        $this->assertDatabaseHas('account_users', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'member_type' => 'tenant',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('tenant_members', [
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'can_login' => 1,
        ]);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.revoke', ['user' => $tenant->id]))
            ->assertRedirect(route('admin.portal-access.index'));

        $this->assertDatabaseHas('account_users', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'status' => 'disabled',
            'can_login' => 0,
        ]);
        $this->assertDatabaseHas('tenant_members', [
            'account_id' => $accountId,
            'user_id' => $tenant->id,
            'can_login' => 0,
        ]);
    }

    public function test_portal_access_invite_hides_tenancies_without_a_property(): void
    {
        [$landlord, $accountId] = $this->createLandlord();

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '12 Linked Tenancy Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $linked = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 950,
        ]);

        $orphan = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => null,
            'status' => 'Active',
            'rent' => 800,
        ]);

        $this->actingAs($landlord)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.portal-access.index'))
            ->assertOk()
            ->assertSee('12 Linked Tenancy Street', false)
            ->assertSee('value="'.$linked->id.'"', false)
            ->assertDontSee('value="'.$orphan->id.'"', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Isolation Landlord '.uniqid(),
            'email' => 'isolation-landlord-'.uniqid().'@resisquare.test',
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
    private function createMembershipOnAccount(int $accountId, string $roleName, string $memberType): array
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::create([
            'name' => $roleName.' '.uniqid(),
            'email' => strtolower($roleName).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $memberType,
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user];
    }
}
