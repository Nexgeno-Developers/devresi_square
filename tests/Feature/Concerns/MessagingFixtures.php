<?php

namespace Tests\Feature\Concerns;

use App\Models\ComplianceBooking;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceType;
use App\Models\ContractorProfile;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\Messaging\MessagingBadge;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

trait MessagingFixtures
{
    protected function setUpMessaging(): void
    {
        MessagingBadge::flush();
        config(['messaging.enabled' => true]);
    }

    protected function user(string $label, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $label.' '.uniqid(),
            'email' => strtolower(str_replace(' ', '-', $label)).'-'.uniqid().'@messaging.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ], $attributes));
    }

    /** @return array{0: User, 1: int} owner and account id */
    protected function landlord(string $type = 'landlord'): array
    {
        $owner = $this->user('Landlord');
        $owner->assignRole(Role::findOrCreate('Landlord', 'web'));
        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $owner->id,
            'account_type' => $type,
            'account_name' => 'Lettings '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->member($accountId, $owner, 'owner');

        return [$owner, $accountId];
    }

    protected function member(int $accountId, User $user, string $type, string $status = 'active', bool $canLogin = true): void
    {
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $type,
            'access_level' => 'full',
            'can_login' => $canLogin ? 1 : 0,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function staff(int $accountId, string $status = 'active'): User
    {
        $staff = $this->user('Staff');
        $this->member($accountId, $staff, 'staff', $status);

        return $staff;
    }

    protected function property(int $accountId): Property
    {
        return Property::create([
            'account_id' => $accountId,
            'line_1' => uniqid().' Message Street',
            'city' => 'London',
            'postcode' => 'E1 6AN',
        ]);
    }

    /**
     * A tenant on a new tenancy (at a new property unless one is given).
     *
     * @return array{0: User, 1: Tenancy, 2: TenantMember}
     */
    protected function tenant(int $accountId, string $status = 'Active', ?Property $property = null): array
    {
        $property ??= $this->property($accountId);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => $status,
            'rent' => 1000,
            'move_in' => now()->subMonth()->toDateString(),
        ]);
        [$tenant, $member] = $this->addTenant($tenancy);

        return [$tenant, $tenancy, $member];
    }

    /** @return array{0: User, 1: TenantMember} */
    protected function addTenant(Tenancy $tenancy): array
    {
        $tenant = $this->user('Tenant');
        $this->member((int) $tenancy->account_id, $tenant, 'tenant');
        $member = TenantMember::create([
            'account_id' => $tenancy->account_id,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'can_login' => 1,
            'is_main_person' => 1,
        ]);

        return [$tenant, $member];
    }

    protected function contractorUser(bool $approved = true): User
    {
        $user = $this->user('Contractor');
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));
        ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => 'Pipe '.uniqid(),
            'vat_registered' => false,
            'cis_status' => 'unverified',
            'employs_staff' => false,
            'postcode' => 'M1 1AA',
            'approval_status' => $approved ? ContractorProfile::APPROVED : ContractorProfile::SUBMITTED,
            'terms_accepted_at' => now(),
        ]);

        return $user->fresh();
    }

    protected function repairOffer(int $accountId, User $reporter, User $contractor, string $status = RepairJobOffer::JOB_ACCEPTED, ?Property $property = null, ?int $repairIssueId = null): RepairJobOffer
    {
        if (! $repairIssueId) {
            $property ??= $this->property($accountId);
            $category = RepairCategory::query()->orderBy('id')->first()
                ?: RepairCategory::create(['name' => 'Messaging', 'parent_id' => null, 'level' => 1, 'description' => 'Test', 'status' => 1, 'position' => 0]);
            $repairIssueId = RepairIssue::create([
                'account_id' => $accountId,
                'property_id' => $property->id,
                'tenant_id' => $reporter->id,
                'repair_category_id' => $category->id,
                'repair_navigation' => json_encode(['Messaging']),
                'description' => 'Leak',
                'priority' => 'medium',
                'sub_status' => 'Pending',
                'status' => 'Pending',
                'reference_number' => 'MSG-'.uniqid(),
                'created_by' => $reporter->id,
            ])->id;
        }

        return RepairJobOffer::create([
            'account_id' => $accountId,
            'repair_issue_id' => $repairIssueId,
            'contractor_user_id' => $contractor->id,
            'invited_by' => $reporter->id,
            'status' => $status,
        ]);
    }

    protected function complianceOffer(int $accountId, User $landlord, User $contractor, Property $property, string $status = ComplianceJobOffer::JOB_ACCEPTED): ComplianceJobOffer
    {
        $type = ComplianceType::query()->firstOrCreate(['alias' => 'gas'], ['name' => 'Gas Safety']);
        $booking = ComplianceBooking::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
            'trade' => 'gas',
            'status' => 'booked',
        ]);

        return ComplianceJobOffer::create([
            'account_id' => $accountId,
            'compliance_booking_id' => $booking->id,
            'contractor_user_id' => $contractor->id,
            'invited_by' => $landlord->id,
            'status' => $status,
        ]);
    }

    protected function chatFor(string $kind, int $contextId): ?Conversation
    {
        return Conversation::query()->where('kind', $kind)->where('context_id', $contextId)->with('participants')->first();
    }

    protected function asAccount(User $user, int $accountId)
    {
        MessagingBadge::flush();

        return $this->actingAs($user)->withSession(['current_account_id' => $accountId]);
    }
}
