<?php

namespace Tests\Feature;

use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\OwnerGroup;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenancyCorrectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 9: two-account IDOR matrix for every IN-scope module.
 * Account A must never read/write Account B resources (403 or 404).
 */
class InScopeIdorMatrixHttpTest extends TestCase
{
    public function test_property_tenancy_finance_document_repair_matrix(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();
        $sessionA = ['current_account_id' => $accountA];

        $propertyB = Property::create([
            'account_id' => $accountB,
            'created_by' => $landlordB->id,
            'line_1' => '1 Foreign Matrix Street',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);

        $tenancyB = Tenancy::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'status' => 'Active',
            'rent' => 1100,
            'move_in' => now()->toDateString(),
        ]);

        $invoiceB = RentInvoice::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'tenancy_id' => $tenancyB->id,
            'tenant_user_id' => $landlordB->id,
            'invoice_no' => 'RENT-ISO-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 1100,
            'balance' => 1100,
            'status' => RentInvoice::STATUS_ISSUED,
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
                'name' => 'IDOR matrix',
                'parent_id' => null,
                'level' => 1,
                'description' => 'Test',
                'status' => 1,
                'position' => 0,
            ]);

        $repairB = RepairIssue::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'tenant_id' => $landlordB->id,
            'final_contractor_id' => $landlordB->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['IDOR matrix']),
            'description' => 'Secret repair',
            'priority' => 'medium',
            'sub_status' => 'Pending',
            'status' => 'Pending',
            'reference_number' => 'IDOR-'.uniqid(),
            'created_by' => $landlordB->id,
        ]);

        $asA = fn () => $this->actingAs($landlordA)->withSession($sessionA);

        $this->assertDenied($asA()->get(route('admin.properties.edit', ['id' => $propertyB->id])));
        $this->assertDenied($asA()->post(route('admin.properties.delete', ['id' => $propertyB->id])));

        $this->assertDenied($asA()->get(route('admin.tenancies.show', ['id' => $tenancyB->id])));
        $this->assertDenied($asA()->get(route('admin.tenancies.edit', ['id' => $tenancyB->id])));
        $this->assertDenied($asA()->get(route('admin.tenancies.rent-ledger', ['id' => $tenancyB->id])));
        $this->assertDenied($asA()->post(route('admin.tenancies.delete', ['id' => $tenancyB->id])));

        $this->assertDenied($asA()->get(route('admin.finance.show', $invoiceB)));
        $this->assertDenied($asA()->post(route('admin.finance.void', $invoiceB)));
        $this->assertDenied($asA()->post(route('admin.finance.payments.store', $invoiceB), [
            'amount' => 10,
            'paid_at' => now()->toDateString(),
            'method' => 'bank_transfer',
        ]));

        $this->assertDenied($asA()->get(route('admin.documents.show', ['id' => $documentId])));
        $this->assertDenied($asA()->post(route('admin.documents.delete', ['id' => $documentId])));

        $this->assertDenied($asA()->get(route('admin.property_repairs.show', ['id' => $repairB->id])));
        $this->assertDenied($asA()->get(route('admin.property_repairs.edit', ['id' => $repairB->id])));
        $this->assertDenied($asA()->delete(route('admin.property_repairs.delete', ['id' => $repairB->id])));

        $this->assertDatabaseHas('properties', ['id' => $propertyB->id, 'account_id' => $accountB]);
        $this->assertDatabaseHas('tenancies', ['id' => $tenancyB->id, 'account_id' => $accountB]);
        $this->assertDatabaseHas('rent_invoices', ['id' => $invoiceB->id, 'balance' => 1100]);
        $this->assertDatabaseHas('repair_issues', ['id' => $repairB->id]);
    }

    public function test_notes_compliance_owner_group_correction_matrix(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();
        $sessionA = ['current_account_id' => $accountA];

        $propertyB = Property::create([
            'account_id' => $accountB,
            'created_by' => $landlordB->id,
            'line_1' => '2 Foreign Matrix Lane',
            'city' => 'London',
            'postcode' => 'N1 9GU',
        ]);

        $tenancyB = Tenancy::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'status' => 'Active',
            'rent' => 900,
        ]);

        $noteId = \DB::table('notes')->insertGetId([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'user_id' => $landlordB->id,
            'content' => 'Secret note',
            'type' => 'general',
            'visibility' => 'private',
            'created_by' => $landlordB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $complianceType = ComplianceType::query()->orderBy('id')->first()
            ?: ComplianceType::create([
                'name' => 'Gas Safety IDOR',
                'alias' => 'gas-safety-idor-'.uniqid(),
                'description' => 'Test',
            ]);

        $compliance = ComplianceRecord::create([
            'property_id' => $propertyB->id,
            'compliance_type_id' => $complianceType->id,
            'issued_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $group = OwnerGroup::create([
            'property_id' => $propertyB->id,
            'purchased_date' => now()->toDateString(),
            'status' => 'active',
            'added_by' => $landlordB->id,
        ]);

        $correction = TenancyCorrectionRequest::create([
            'account_id' => $accountB,
            'tenancy_id' => $tenancyB->id,
            'requested_by' => $landlordB->id,
            'status' => TenancyCorrectionRequest::STATUS_PENDING,
            'message' => 'Please fix rent',
            'fields' => [['key' => 'rent', 'label' => 'Rent', 'current' => '900', 'requested' => '950']],
            'snapshot' => ['rent' => 900],
        ]);

        $asA = fn () => $this->actingAs($landlordA)->withSession($sessionA);

        $this->assertDenied($asA()->get(route('admin.notes.show', ['id' => $noteId])));
        $this->assertDenied($asA()->post(route('admin.notes.delete', ['id' => $noteId])));

        $this->assertDenied($asA()->delete(route('admin.compliance.delete', ['complianceRecordId' => $compliance->id])));

        $this->assertDenied($asA()->get(route('admin.owner-groups.show', ['ownerGroup' => $group->id])));

        $this->assertDenied($asA()->post(
            route('admin.tenancies.correction-requests.approve', [
                'tenancy' => $tenancyB->id,
                'correction' => $correction->id,
            ]),
            ['rent' => 950]
        ));

        $this->assertDenied($asA()->post(
            route('admin.tenancies.correction-requests.reject', [
                'tenancy' => $tenancyB->id,
                'correction' => $correction->id,
            ]),
            ['landlord_note' => 'Nope']
        ));

        // Cannot create finance against a foreign tenancy (validation redirect is fail-closed).
        $financeResponse = $asA()->from(route('admin.finance.create'))
            ->post(route('admin.finance.store'), [
                'tenancy_id' => $tenancyB->id,
                'tenant_user_id' => $landlordB->id,
                'amount' => 50,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);
        $this->assertContains($financeResponse->status(), [302, 403, 404, 422]);
        $this->assertDatabaseMissing('rent_invoices', [
            'account_id' => $accountA,
            'tenancy_id' => $tenancyB->id,
        ]);

        $this->assertDatabaseHas('notes', ['id' => $noteId]);
        $this->assertDatabaseHas('compliance_records', ['id' => $compliance->id]);
        $this->assertDatabaseHas('tenancy_correction_requests', [
            'id' => $correction->id,
            'status' => TenancyCorrectionRequest::STATUS_PENDING,
        ]);
    }

    public function test_portal_invite_rejects_foreign_tenancy(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();

        $propertyB = Property::create([
            'account_id' => $accountB,
            'created_by' => $landlordB->id,
            'line_1' => '3 Foreign Invite Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancyB = Tenancy::create([
            'account_id' => $accountB,
            'property_id' => $propertyB->id,
            'status' => 'Active',
            'rent' => 800,
        ]);

        $email = 'cross-invite-'.uniqid().'@resisquare.test';

        $response = $this->actingAs($landlordA)
            ->withSession(['current_account_id' => $accountA])
            ->from(route('admin.portal-access.index'))
            ->post(route('admin.portal-access.invite'), [
                'name' => 'Cross Account Tenant',
                'email' => $email,
                'tenancy_id' => $tenancyB->id,
            ]);

        $this->assertContains($response->status(), [302, 403, 404, 422]);
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    private function assertDenied($response): void
    {
        $status = $response->status();
        $this->assertContains(
            $status,
            [403, 404, 422],
            'Expected fail-closed IDOR response, got '.$status
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'IDOR Landlord '.uniqid(),
            'email' => 'idor-landlord-'.uniqid().'@resisquare.test',
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
}
