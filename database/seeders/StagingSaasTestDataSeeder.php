<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountSubscriptionAddon;
use App\Models\AccountUser;
use App\Models\Addon;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Property;
use App\Models\PropertyParticipant;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairIssueContractorAssignment;
use App\Models\RepairIssuePropertyManager;
use App\Models\Staff;
use App\Models\Tenancy;
use App\Models\TenancySubStatus;
use App\Models\TenancyType;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StagingSaasTestDataSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Refusing to seed staging SaaS test data in production.');
        }

        $this->assertTables([
            'users',
            'roles',
            'accounts',
            'account_users',
            'plans',
            'addons',
            'account_subscriptions',
            'account_subscription_addons',
            'properties',
            'property_participants',
            'tenancies',
            'tenant_members',
            'repair_categories',
            'repair_issues',
            'repair_issue_contractor_assignments',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            $this->createRoles([
                'Super Admin',
                'Landlord',
                'Estate Agent',
                'Staff',
                'Tenant',
                'Contractor',
                'Property Manager',
            ]);

            $admin = $this->user('admin@resisquare.test', 'Staging', 'Super Admin', 'super_admin', ['Super Admin']);
            $landlordOwner = $this->user('landlord.owner@resisquare.test', 'Lara', 'Landlord', 'landlord', ['Landlord']);
            $estateOwner = $this->user('estate.owner@resisquare.test', 'Evan', 'Estate', 'estate_agent', ['Estate Agent']);
            $estateStaff = $this->user('estate.staff@resisquare.test', 'Sara', 'Staff', 'staff', ['Staff']);
            $landlordContact = $this->user('landlord.contact@resisquare.test', 'Chris', 'Contact', 'landlord', ['Landlord']);
            $tenant = $this->user('tenant@resisquare.test', 'Tina', 'Tenant', 'tenant', ['Tenant']);
            $contractor = $this->user('contractor@resisquare.test', 'Carl', 'Contractor', 'contractor', ['Contractor']);
            $propertyManager = $this->user('property.manager@resisquare.test', 'Priya', 'Manager', 'property_manager', ['Property Manager']);

            $landlordPlan = $this->plan('landlord_basic', [
                'name' => 'Landlord Basic',
                'target_account_type' => 'landlord',
                'description' => 'Staging landlord plan with one property and no branch or staff access.',
                'monthly_price_minor' => 2900,
                'annual_price_minor' => 29000,
                'property_limit' => 1,
                'branch_limit' => 0,
                'staff_limit' => 0,
                'property_manager_limit' => 0,
                'allow_company_profile' => false,
                'allow_invoice_branding' => false,
                'allow_roles_permissions' => false,
                'allow_contact_login' => true,
                'sort_order' => 10,
            ]);

            $estatePlan = $this->plan('estate_agent_company', [
                'name' => 'Estate Agent Company',
                'target_account_type' => 'estate_agent_company',
                'description' => 'Staging estate-agent company plan with two properties, one branch, and one staff user.',
                'monthly_price_minor' => 14900,
                'annual_price_minor' => 149000,
                'property_limit' => 2,
                'branch_limit' => 1,
                'staff_limit' => 1,
                'property_manager_limit' => 1,
                'allow_company_profile' => true,
                'allow_invoice_branding' => true,
                'allow_roles_permissions' => true,
                'allow_contact_login' => true,
                'sort_order' => 20,
            ]);

            $extraProperty = $this->addon('extra_property', 'Extra Property', 'property', 1, 500, 5000);
            $this->addon('extra_staff', 'Extra Staff', 'staff', 1, 1000, 10000);
            $this->addon('extra_branch', 'Extra Branch', 'branch', 1, 2000, 20000);
            $propertyManagerAddon = $this->addon('property_manager', 'Property Manager', 'property_manager', 1, 1500, 15000);

            $landlordAccount = $this->account($landlordOwner, 'landlord', 'Staging Landlord Account');
            $estateAccount = $this->account($estateOwner, 'estate_agent_company', 'Staging Estate Agent Account');

            $landlordSubscription = $this->subscription($landlordAccount, $landlordPlan, 'monthly');
            $estateSubscription = $this->subscription($estateAccount, $estatePlan, 'annual');

            $this->subscriptionAddon($landlordSubscription, $propertyManagerAddon, 1);
            $this->subscriptionAddon($estateSubscription, $extraProperty, 1, 'cancelled');

            $this->membership($landlordAccount, $landlordOwner, 'owner', 'full', true, $admin);
            $this->membership($landlordAccount, $landlordContact, 'landlord', 'view', true, $landlordOwner);
            $this->membership($landlordAccount, $tenant, 'tenant', 'view', true, $landlordOwner);
            $this->membership($landlordAccount, $contractor, 'contractor', 'view', true, $landlordOwner);
            $this->membership($landlordAccount, $propertyManager, 'property_manager', 'full', true, $landlordOwner);

            $this->membership($estateAccount, $estateOwner, 'owner', 'full', true, $admin);
            $this->membership($estateAccount, $estateStaff, 'staff', 'edit', true, $estateOwner);

            $company = $this->company($estateAccount, $estateOwner);
            $branch = $this->branch($estateAccount, $company, $estateOwner);
            $staff = $this->staff($estateAccount, $estateStaff, $estateOwner, $branch);
            $estateStaff->forceFill($this->filterColumns('users', [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]))->save();

            $landlordProperty = $this->property($landlordAccount, null, null, $landlordOwner, [
                'prop_ref_no' => 'STG-LAND-001',
                'prop_name' => 'Staging Landlord Property',
                'line_1' => '1 Staging Landlord Street',
                'city' => 'London',
                'postcode' => 'ST1 1AA',
            ]);

            $estateProperty = $this->property($estateAccount, $company, $branch, $estateOwner, [
                'prop_ref_no' => 'STG-EST-001',
                'prop_name' => 'Staging Estate Property',
                'line_1' => '10 Staging Estate Avenue',
                'city' => 'London',
                'postcode' => 'ST2 2AA',
            ]);

            $this->property($estateAccount, $company, $branch, $estateOwner, [
                'prop_ref_no' => 'STG-EST-002',
                'prop_name' => 'Staging Estate Unassigned Property',
                'line_1' => '11 Staging Estate Avenue',
                'city' => 'London',
                'postcode' => 'ST2 2AB',
            ]);

            $tenancy = $this->tenancy($landlordAccount, $landlordProperty, $tenant);
            $this->tenantMember($landlordAccount, $tenancy, $tenant);
            $this->propertyManagerTenancy($tenancy, $propertyManager, $landlordProperty);

            $repair = $this->repair($landlordAccount, $landlordProperty, $tenant, $contractor, $landlordOwner);
            $this->contractorAssignment($landlordAccount, $repair, $contractor, $propertyManager);
            $this->propertyManagerRepairAssignment($landlordAccount, $repair, $propertyManager, $landlordOwner);
            $this->workOrder($landlordAccount, $repair, $contractor, $landlordOwner);

            $this->participant($landlordAccount, $landlordProperty, $landlordContact, 'landlord', 'view', true, true, true, $landlordOwner);
            $this->participant($landlordAccount, $landlordProperty, $tenant, 'tenant', 'view', false, true, false, $landlordOwner);
            $this->participant($landlordAccount, $landlordProperty, $contractor, 'contractor', 'view', false, false, false, $landlordOwner);
            $this->participant($landlordAccount, $landlordProperty, $propertyManager, 'property_manager', 'full', true, true, true, $landlordOwner);

            $this->command?->info('Created staging SaaS users with password: ' . self::PASSWORD);
            $this->command?->table(
                ['Role', 'Email'],
                [
                    ['Super Admin', $admin->email],
                    ['Landlord Owner', $landlordOwner->email],
                    ['Estate Agent Owner', $estateOwner->email],
                    ['Estate Agent Staff', $estateStaff->email],
                    ['Landlord Contact', $landlordContact->email],
                    ['Tenant', $tenant->email],
                    ['Contractor', $contractor->email],
                    ['Property Manager', $propertyManager->email],
                ]
            );
        });
    }

    private function createRoles(array $roles): void
    {
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    private function user(string $email, string $firstName, string $lastName, string $userType, array $roles): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            $this->filterColumns('users', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim($firstName . ' ' . $lastName),
                'user_type' => $userType,
                'password' => Hash::make(self::PASSWORD),
                'phone' => '+440000000000',
                'status' => 1,
                'can_login' => 1,
                'email_verified_at' => now(),
            ])
        );

        foreach ($roles as $role) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        return $user->refresh();
    }

    private function plan(string $code, array $attributes): Plan
    {
        return Plan::updateOrCreate(
            ['code' => $code],
            $this->filterColumns('plans', array_merge([
                'code' => $code,
                'currency' => 'GBP',
                'trial_days' => 7,
                'is_active' => true,
            ], $attributes))
        );
    }

    private function addon(string $code, string $name, string $type, int $grant, int $monthly, int $annual): Addon
    {
        return Addon::updateOrCreate(
            ['code' => $code],
            $this->filterColumns('addons', [
                'code' => $code,
                'name' => $name,
                'addon_type' => $type,
                'monthly_price_minor' => $monthly,
                'annual_price_minor' => $annual,
                'currency' => 'GBP',
                'grant_quantity' => $grant,
                'is_stackable' => true,
                'is_active' => true,
            ])
        );
    }

    private function account(User $owner, string $type, string $name): Account
    {
        $account = Account::updateOrCreate(
            [
                'owner_user_id' => $owner->id,
                'account_name' => $name,
            ],
            $this->filterColumns('accounts', [
                'owner_user_id' => $owner->id,
                'account_type' => $type,
                'account_name' => $name,
                'billing_email' => $owner->email,
                'billing_phone' => $owner->phone,
                'currency' => 'GBP',
                'status' => 'trialing',
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addDays(7),
            ])
        );

        $owner->forceFill($this->filterColumns('users', [
            'last_active_account_id' => $account->id,
        ]))->save();

        return $account;
    }

    private function subscription(Account $account, Plan $plan, string $billingCycle): AccountSubscription
    {
        return AccountSubscription::updateOrCreate(
            [
                'account_id' => $account->id,
                'plan_id' => $plan->id,
            ],
            $this->filterColumns('account_subscriptions', [
                'account_id' => $account->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => 'trialing',
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addDays(7),
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
                'stripe_subscription_id' => null,
                'stripe_price_id' => null,
                'price_at_signup_minor' => $billingCycle === 'annual' ? $plan->annual_price_minor : $plan->monthly_price_minor,
                'currency_at_signup' => $plan->currency ?: 'GBP',
                'plan_name_at_signup' => $plan->name,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
            ])
        );
    }

    private function subscriptionAddon(AccountSubscription $subscription, Addon $addon, int $quantity, string $status = 'active'): AccountSubscriptionAddon
    {
        return AccountSubscriptionAddon::updateOrCreate(
            [
                'account_subscription_id' => $subscription->id,
                'addon_id' => $addon->id,
            ],
            $this->filterColumns('account_subscription_addons', [
                'account_subscription_id' => $subscription->id,
                'account_id' => $subscription->account_id,
                'addon_id' => $addon->id,
                'quantity' => $quantity,
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $status,
                'price_at_purchase_minor' => $subscription->billing_cycle === 'annual' ? $addon->annual_price_minor : $addon->monthly_price_minor,
                'addon_name_at_purchase' => $addon->name,
            ])
        );
    }

    private function membership(Account $account, User $user, string $memberType, string $accessLevel, bool $canLogin, User $createdBy): AccountUser
    {
        $user->forceFill($this->filterColumns('users', [
            'last_active_account_id' => $account->id,
        ]))->save();

        return AccountUser::updateOrCreate(
            [
                'account_id' => $account->id,
                'user_id' => $user->id,
            ],
            $this->filterColumns('account_users', [
                'account_id' => $account->id,
                'user_id' => $user->id,
                'member_type' => $memberType,
                'access_level' => $accessLevel,
                'can_login' => $canLogin,
                'status' => 'active',
                'created_by' => $createdBy->id,
            ])
        );
    }

    private function company(Account $account, User $owner): Company
    {
        return Company::updateOrCreate(
            ['owner_user_id' => $owner->id],
            $this->filterColumns('companies', [
                'account_id' => $account->id,
                'owner_user_id' => $owner->id,
                'name' => 'Staging Estate Agent Ltd',
                'company_type' => 'agency_company',
                'emails' => [$owner->email],
                'phones' => [$owner->phone],
                'status' => 'active',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ])
        );
    }

    private function branch(Account $account, Company $company, User $owner): Branch
    {
        return Branch::updateOrCreate(
            [
                'account_id' => $account->id,
                'name' => 'Staging Head Office',
            ],
            $this->filterColumns('branches', [
                'account_id' => $account->id,
                'company_id' => $company->id,
                'is_main_head_office' => true,
                'name' => 'Staging Head Office',
                'address' => '20 Staging Branch Road',
                'address_line_1' => '20 Staging Branch Road',
                'city' => 'London',
                'postcode' => 'ST3 3AA',
                'country' => 'UK',
                'user_email' => 'estate.office@resisquare.test',
                'user_phone' => '+440000000001',
                'status' => 'active',
                'created_by' => $owner->id,
            ])
        );
    }

    private function staff(Account $account, User $staffUser, User $owner, Branch $branch): Staff
    {
        return Staff::updateOrCreate(
            ['user_id' => $staffUser->id],
            $this->filterColumns('staff', [
                'account_id' => $account->id,
                'user_id' => $staffUser->id,
                'parent_id' => $owner->id,
                'branch_id' => $branch->id,
                'permissions_customized' => false,
                'status' => 'active',
            ])
        );
    }

    private function property(Account $account, ?Company $company, ?Branch $branch, User $owner, array $attributes): Property
    {
        return Property::updateOrCreate(
            ['prop_ref_no' => $attributes['prop_ref_no']],
            $this->filterColumns('properties', array_merge([
                'account_id' => $account->id,
                'company_id' => $company?->id,
                'branch_id' => $branch?->id,
                'country' => null,
                'currency' => 'GBP',
                'property_type' => 'Residential',
                'transaction_type' => 'Lettings',
                'specific_property_type' => 'Flat',
                'bedroom' => '2',
                'bathroom' => '1',
                'reception' => '1',
                'letting_current_status' => 'Available',
                'letting_price' => 1250,
                'price' => 1250,
                'created_by' => $owner->id,
                'added_by' => $owner->id,
            ], $attributes))
        );
    }

    private function tenancy(Account $account, Property $property, User $tenant): Tenancy
    {
        $type = TenancyType::firstOrCreate(['name' => 'Assured Shorthold Tenancy']);
        $subStatus = TenancySubStatus::firstOrCreate(['name' => 'Active']);

        return Tenancy::updateOrCreate(
            [
                'property_id' => $property->id,
                'move_in' => '2026-01-01',
            ],
            $this->filterColumns('tenancies', [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => '2026-01-01',
                'move_out' => '2026-12-31',
                'rent' => 1250,
                'deposit' => 1442.31,
                'deposit_type' => 'weeks_deposit',
                'deposit_number' => 5,
                'frequency' => 'Monthly',
                'tenancy_type_id' => $type->id,
                'tenancy_sub_status_id' => $subStatus->id,
                'created_by' => $tenant->id,
            ])
        );
    }

    private function tenantMember(Account $account, Tenancy $tenancy, User $tenant): TenantMember
    {
        return TenantMember::updateOrCreate(
            [
                'tenancy_id' => $tenancy->id,
                'user_id' => $tenant->id,
            ],
            $this->filterColumns('tenant_members', [
                'account_id' => $account->id,
                'tenancy_id' => $tenancy->id,
                'user_id' => $tenant->id,
                'is_main_person' => true,
                'group_id' => 'STG-TENANCY-' . $tenancy->id,
                'access_level' => 'view',
                'can_login' => true,
            ])
        );
    }

    private function repair(Account $account, Property $property, User $tenant, User $contractor, User $createdBy): RepairIssue
    {
        $category = RepairCategory::firstOrCreate(
            ['name' => 'Staging General Repair'],
            [
                'level' => 1,
                'description' => 'General staging repair category.',
                'icon' => 'placeholder.svg',
                'position' => 999,
                'status' => true,
            ]
        );

        return RepairIssue::updateOrCreate(
            ['reference_number' => 'STG-REP-001'],
            $this->filterColumns('repair_issues', [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'repair_category_id' => $category->id,
                'repair_navigation' => json_encode(['Staging General Repair']),
                'description' => 'Staging repair for contractor portal testing.',
                'tenant_id' => $tenant->id,
                'tenant_availability' => now()->addDays(2),
                'access_details' => 'Use staging lockbox code 0000.',
                'estimated_price' => 150,
                'vat_type' => 'exclusive',
                'priority' => 'medium',
                'sub_status' => 'Assigned',
                'status' => 'Open',
                'final_contractor_id' => $contractor->id,
                'reference_number' => 'STG-REP-001',
                'created_by' => $createdBy->id,
                'updated_by' => $createdBy->id,
            ])
        );
    }

    private function contractorAssignment(Account $account, RepairIssue $repair, User $contractor, User $assignedBy): RepairIssueContractorAssignment
    {
        return RepairIssueContractorAssignment::updateOrCreate(
            [
                'repair_issue_id' => $repair->id,
                'contractor_id' => $contractor->id,
            ],
            $this->filterColumns('repair_issue_contractor_assignments', [
                'account_id' => $account->id,
                'repair_issue_id' => $repair->id,
                'contractor_id' => $contractor->id,
                'assigned_by' => $assignedBy->id,
                'cost_price' => 150,
                'contractor_preferred_availability' => now()->addDays(3),
                'status' => 'Assigned',
                'quote_token' => 'staging-quote-token-001',
                'quote_requested_at' => now(),
            ])
        );
    }

    private function propertyManagerRepairAssignment(Account $account, RepairIssue $repair, User $propertyManager, User $assignedBy): RepairIssuePropertyManager
    {
        return RepairIssuePropertyManager::updateOrCreate(
            [
                'repair_issue_id' => $repair->id,
                'property_manager_id' => $propertyManager->id,
            ],
            $this->filterColumns('repair_issue_property_managers', [
                'account_id' => $account->id,
                'repair_issue_id' => $repair->id,
                'property_manager_id' => $propertyManager->id,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy->id,
                'notes' => 'Staging property manager repair assignment.',
            ])
        );
    }

    private function propertyManagerTenancy(Tenancy $tenancy, User $propertyManager, Property $property): object
    {
        return $this->upsertTable('property_manager_tenancy', [
            'tenancy_id' => $tenancy->id,
            'property_manager_id' => $propertyManager->id,
            'property_id' => $property->id,
        ], []);
    }

    private function workOrder(Account $account, RepairIssue $repair, User $contractor, User $owner): ?object
    {
        if (! Schema::hasTable('work_orders')) {
            return null;
        }

        $invoiceId = null;
        if (Schema::hasColumn('work_orders', 'invoices') && ! $this->columnIsNullable('work_orders', 'invoices')) {
            $invoice = $this->legacyInvoice($repair, $owner);
            if (! $invoice) {
                $this->command?->warn('Skipped staging work order because legacy work_orders.invoices is required but no invoice could be created.');

                return null;
            }

            $invoiceId = $invoice->id;
        }

        $workOrder = $this->upsertTable('work_orders', [
            'works_order_no' => 'STG-WO-001',
        ], [
            'account_id' => $account->id,
            'works_order_no' => 'STG-WO-001',
            'repair_issue_id' => $repair->id,
            'job_status' => 'Assigned',
            'job_scope' => 'Staging work order for contractor portal testing.',
            'invoice_to' => 'landlord',
            'invoice_to_id' => $owner->id,
            'tentative_start_date' => now()->addDays(3)->toDateString(),
            'tentative_end_date' => now()->addDays(5)->toDateString(),
            'estimated_cost' => 150,
            'status' => 'Open',
            'invoices' => $invoiceId,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        if ($invoiceId && Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'work_order_id')) {
            DB::table('invoices')->where('id', $invoiceId)->update(['work_order_id' => $workOrder->id]);
        }

        return $workOrder;
    }

    private function legacyInvoice(RepairIssue $repair, User $owner): ?object
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        try {
            return $this->upsertTable('invoices', [
                'invoice_number' => 'STG-WO-INV-001',
            ], [
                'invoice_number' => 'STG-WO-INV-001',
                'property_id' => $repair->property_id,
                'user_id' => $owner->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'subtotal' => 150,
                'tax_amount' => 0,
                'total_amount' => 150,
                'notes' => 'Legacy staging work-order invoice placeholder.',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function participant(
        Account $account,
        Property $property,
        User $user,
        string $type,
        string $accessLevel,
        bool $canViewFinance,
        bool $canViewDocuments,
        bool $canUploadDocuments,
        User $createdBy
    ): PropertyParticipant {
        return PropertyParticipant::updateOrCreate(
            [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'user_id' => $user->id,
                'participant_type' => $type,
            ],
            $this->filterColumns('property_participants', [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'user_id' => $user->id,
                'participant_type' => $type,
                'access_level' => $accessLevel,
                'can_view_finance' => $canViewFinance,
                'can_view_documents' => $canViewDocuments,
                'can_upload_documents' => $canUploadDocuments,
                'status' => 'active',
                'created_by' => $createdBy->id,
            ])
        );
    }

    private function assertTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Missing required table [{$table}]. Run migrations before staging seed.");
            }
        }
    }

    private function filterColumns(string $table, array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function upsertTable(string $table, array $unique, array $values): object
    {
        $unique = $this->filterColumns($table, $unique);
        $values = $this->filterColumns($table, $values);
        $query = DB::table($table);

        foreach ($unique as $column => $value) {
            $query->where($column, $value);
        }

        $existing = $query->first();
        $now = now();

        if ($existing) {
            if (Schema::hasColumn($table, 'updated_at')) {
                $values['updated_at'] = $now;
            }

            if ($values !== []) {
                DB::table($table)->where('id', $existing->id)->update($values);
            }

            return DB::table($table)->where('id', $existing->id)->first() ?: $existing;
        }

        $insert = array_merge($unique, $values);

        if (Schema::hasColumn($table, 'created_at')) {
            $insert['created_at'] = $now;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $insert['updated_at'] = $now;
        }

        $id = DB::table($table)->insertGetId($insert);

        return DB::table($table)->where('id', $id)->first();
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        try {
            foreach (Schema::getColumns($table) as $definition) {
                if (($definition['name'] ?? null) === $column) {
                    return (bool) ($definition['nullable'] ?? true);
                }
            }
        } catch (\Throwable) {
            return true;
        }

        return true;
    }
}
