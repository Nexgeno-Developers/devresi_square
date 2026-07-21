<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('users', 'last_active_account_id', fn (Blueprint $table) => $table->unsignedBigInteger('last_active_account_id')->nullable());
        $this->indexIfMissing('users', ['last_active_account_id'], 'users_last_active_account_idx');

        $this->addColumnIfMissing('companies', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('companies', 'company_type', fn (Blueprint $table) => $table->enum('company_type', ['agency_company', 'freelance_profile'])->nullable());
        $this->addColumnIfMissing('companies', 'status', fn (Blueprint $table) => $table->enum('status', ['active', 'inactive'])->default('active'));
        $this->indexIfMissing('companies', ['account_id'], 'companies_account_id_idx');

        $this->addColumnIfMissing('branches', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('branches', 'status', fn (Blueprint $table) => $table->enum('status', ['active', 'inactive'])->default('active'));
        $this->indexIfMissing('branches', ['account_id'], 'branches_account_id_idx');

        $this->addColumnIfMissing('staff', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('staff', 'status', fn (Blueprint $table) => $table->enum('status', ['active', 'inactive'])->default('active'));
        $this->indexIfMissing('staff', ['account_id'], 'staff_account_id_idx');

        $this->addColumnIfMissing('designations', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('designations', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('designations', 'status', fn (Blueprint $table) => $table->enum('status', ['active', 'inactive'])->default('active'));
        $this->indexIfMissing('designations', ['account_id'], 'designations_account_id_idx');
        $this->indexIfMissing('designations', ['company_id'], 'designations_company_id_idx');

        $this->addColumnIfMissing('properties', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('properties', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('properties', 'branch_id', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        $this->addColumnIfMissing('properties', 'property_identity_hash', fn (Blueprint $table) => $table->string('property_identity_hash', 64)->nullable());
        $this->addColumnIfMissing('properties', 'uprn', fn (Blueprint $table) => $table->string('uprn', 32)->nullable());
        $this->indexIfMissing('properties', ['account_id'], 'properties_account_id_idx');
        $this->indexIfMissing('properties', ['company_id'], 'properties_company_id_idx');
        $this->indexIfMissing('properties', ['branch_id'], 'properties_branch_id_idx');
        $this->indexIfMissing('properties', ['account_id', 'property_identity_hash'], 'properties_account_identity_unique', true);

        $this->addColumnIfMissing('property_responsibilities', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('property_responsibilities', 'status', fn (Blueprint $table) => $table->enum('status', ['active', 'inactive'])->default('active'));
        $this->addColumnIfMissing('property_responsibilities', 'starts_at', fn (Blueprint $table) => $table->timestamp('starts_at')->nullable());
        $this->addColumnIfMissing('property_responsibilities', 'ends_at', fn (Blueprint $table) => $table->timestamp('ends_at')->nullable());
        $this->indexIfMissing('property_responsibilities', ['account_id'], 'property_responsibilities_account_idx');

        $this->addColumnIfMissing('tenancies', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('tenancies', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('tenancies', 'branch_id', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        $this->indexIfMissing('tenancies', ['account_id'], 'tenancies_account_id_idx');

        $this->addColumnIfMissing('tenant_members', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('tenant_members', 'access_level', fn (Blueprint $table) => $table->enum('access_level', ['view', 'edit'])->default('view'));
        $this->addColumnIfMissing('tenant_members', 'can_login', fn (Blueprint $table) => $table->boolean('can_login')->default(false));
        $this->indexIfMissing('tenant_members', ['account_id'], 'tenant_members_account_id_idx');

        $this->addColumnIfMissing('repair_issues', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('repair_issues', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('repair_issues', 'branch_id', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        $this->indexIfMissing('repair_issues', ['account_id'], 'repair_issues_account_id_idx');

        $this->addColumnIfMissing('repair_issue_contractor_assignments', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->indexIfMissing('repair_issue_contractor_assignments', ['account_id'], 'rica_account_id_idx');

        $this->addColumnIfMissing('repair_issue_property_managers', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->indexIfMissing('repair_issue_property_managers', ['account_id'], 'ripm_account_id_idx');

        $this->addColumnIfMissing('work_orders', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('work_orders', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('work_orders', 'branch_id', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        $this->indexIfMissing('work_orders', ['account_id'], 'work_orders_account_id_idx');

        $this->addColumnIfMissing('documents', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('documents', 'visibility', fn (Blueprint $table) => $table->enum('visibility', ['private', 'shared', 'portal'])->default('private'));
        $this->addColumnIfMissing('documents', 'created_by', fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable());
        $this->indexIfMissing('documents', ['account_id'], 'documents_account_id_idx');

        $this->addColumnIfMissing('notes', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('notes', 'visibility', fn (Blueprint $table) => $table->enum('visibility', ['private', 'shared', 'portal'])->default('private'));
        $this->addColumnIfMissing('notes', 'created_by', fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable());
        $this->indexIfMissing('notes', ['account_id'], 'notes_account_id_idx');

        $this->addColumnIfMissing('uploads', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('uploads', 'visibility', fn (Blueprint $table) => $table->enum('visibility', ['private', 'shared', 'portal'])->default('private'));
        $this->indexIfMissing('uploads', ['account_id'], 'uploads_account_id_idx');

        $this->addColumnIfMissing('events', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->addColumnIfMissing('events', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->addColumnIfMissing('events', 'branch_id', fn (Blueprint $table) => $table->unsignedBigInteger('branch_id')->nullable());
        $this->indexIfMissing('events', ['account_id'], 'events_account_id_idx');

        $this->addColumnIfMissing('registrations', 'account_type', fn (Blueprint $table) => $table->enum('account_type', ['landlord', 'estate_agent_freelance', 'estate_agent_company'])->nullable());
        $this->addColumnIfMissing('registrations', 'plan_id', fn (Blueprint $table) => $table->unsignedBigInteger('plan_id')->nullable());
        $this->addColumnIfMissing('registrations', 'billing_cycle', fn (Blueprint $table) => $table->enum('billing_cycle', ['monthly', 'annual'])->nullable());
        $this->addColumnIfMissing('registrations', 'account_id', fn (Blueprint $table) => $table->unsignedBigInteger('account_id')->nullable());
        $this->indexIfMissing('registrations', ['account_id'], 'registrations_account_id_idx');
        $this->indexIfMissing('registrations', ['plan_id'], 'registrations_plan_id_idx');

        $this->addAccountingAccountColumns();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Do not remove columns from existing production tables.
    }

    private function addAccountingAccountColumns(): void
    {
        $accountTables = [
            'sys_sale_invoices' => 'sys_sale_inv_account_idx',
            'sys_sale_invoice_items' => 'sys_sale_items_account_idx',
            'sys_receipts' => 'sys_receipts_account_idx',
            'sys_payments' => 'sys_payments_account_idx',
            'sys_purchase_invoices' => 'sys_purchase_inv_account_idx',
            'sys_purchase_invoice_items' => 'sys_purchase_items_account_idx',
            'credit_notes' => 'credit_notes_account_idx',
            'debit_notes' => 'debit_notes_account_idx',
            'gl_journals' => 'gl_journals_account_idx',
            'gl_journal_lines' => 'gl_journal_lines_account_idx',
            'gl_account_balances' => 'gl_balances_account_idx',
            'sys_bank_accounts' => 'sys_bank_accounts_account_idx',
            'bank_reconciliations' => 'bank_recs_account_idx',
            'bank_reconciliation_lines' => 'bank_rec_lines_account_idx',
            'gl_accounts' => 'gl_accounts_account_idx',
        ];

        foreach ($accountTables as $table => $indexName) {
            $this->addColumnIfMissing($table, 'account_id', fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('account_id')->nullable());
            $this->indexIfMissing($table, ['account_id'], $indexName);
        }

        $headerTables = [
            'sys_sale_invoices' => ['company' => 'sys_sale_inv_company_idx', 'branch' => 'sys_sale_inv_branch_idx'],
            'sys_purchase_invoices' => ['company' => 'sys_purchase_inv_company_idx', 'branch' => 'sys_purchase_inv_branch_idx'],
            'gl_journals' => ['company' => 'gl_journals_company_idx', 'branch' => 'gl_journals_branch_idx'],
            'sys_bank_accounts' => ['company' => 'sys_bank_accounts_company_idx', 'branch' => 'sys_bank_accounts_branch_idx'],
        ];

        foreach ($headerTables as $table => $indexes) {
            $this->addColumnIfMissing($table, 'company_id', fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('company_id')->nullable());
            $this->addColumnIfMissing($table, 'branch_id', fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('branch_id')->nullable());
            $this->indexIfMissing($table, ['company_id'], $indexes['company']);
            $this->indexIfMissing($table, ['branch_id'], $indexes['branch']);
        }
    }

    private function addColumnIfMissing(string $table, string $column, Closure $definition): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($definition) {
            $definition($blueprint);
        });
    }

    private function indexIfMissing(string $table, array $columns, string $indexName, bool $unique = false): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasIndex($table, $indexName, $columns, $unique)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName, $unique) {
            $unique
                ? $blueprint->unique($columns, $indexName)
                : $blueprint->index($columns, $indexName);
        });
    }

    private function hasIndex(string $table, string $indexName, array $columns, bool $unique): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
        } catch (Throwable) {
            return false;
        }

        $expectedName = strtolower($indexName);
        $expectedColumns = array_map('strtolower', $columns);

        foreach ($indexes as $index) {
            $currentName = strtolower((string) ($index['name'] ?? ''));
            if ($currentName === $expectedName) {
                return true;
            }

            $currentColumns = array_map('strtolower', $index['columns'] ?? []);
            $isUnique = (bool) ($index['unique'] ?? false);

            if ($currentColumns === $expectedColumns && (! $unique || $isUnique)) {
                return true;
            }
        }

        return false;
    }
};
