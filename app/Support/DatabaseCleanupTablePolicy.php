<?php

namespace App\Support;

final class DatabaseCleanupTablePolicy
{
    public const CLEAR = 'CLEAR';

    public const PRESERVE = 'PRESERVE';

    public const REVIEW = 'REVIEW';

    /**
     * Tables containing tenant, operational, transactional, log, or test data.
     *
     * users, model_has_roles, and model_has_permissions are cleared selectively:
     * rows belonging to a Super Admin are protected by the cleanup command.
     */
    private const CLEAR_TABLES = [
        'accounts',
        'account_subscriptions',
        'account_subscription_addons',
        'account_users',
        'audits',
        'bank_accounts',
        'bank_details',
        'bank_reconciliations',
        'bank_reconciliation_lines',
        'branches',
        'cache',
        'cache_locks',
        'companies',
        'company_owner_transfers',
        'compliance_details',
        'compliance_records',
        'documents',
        'estate_charges',
        'estate_charges_items',
        'eventables',
        'events',
        'event_instance_changes',
        'event_reminders',
        'failed_jobs',
        'fixed_assets',
        'form_submissions',
        'gl_account_balances',
        'gl_audit_logs',
        'gl_journals',
        'gl_journal_lines',
        'gl_period_closes',
        'invoices',
        'invoice_items',
        'jobs',
        'job_batches',
        'model_has_permissions',
        'model_has_roles',
        'notes',
        'offers',
        'owner_group',
        'owner_group_users',
        'password_reset_tokens',
        'properties',
        'property_manager_tenancy',
        'property_participants',
        'property_responsibilities',
        'registrations',
        'repair_assignments',
        'repair_histories',
        'repair_issues',
        'repair_issue_contractor_assignments',
        'repair_issue_property_managers',
        'repair_issue_users',
        'repair_photos',
        'sessions',
        'staff',
        'staff_contacts',
        'sys_adjustment_notes',
        'sys_payments',
        'sys_purchase_invoices',
        'sys_purchase_invoice_items',
        'sys_receipts',
        'sys_refunds',
        'sys_sale_invoices',
        'sys_sale_invoice_items',
        'tenancies',
        'tenant_members',
        'transactions',
        'uploads',
        'users',
        'user_details',
        'work_orders',
        'work_order_items',
    ];

    /** Tables containing system-required configuration, permissions, or master data. */
    private const PRESERVE_TABLES = [
        'account_headers',
        'addons',
        'business_settings',
        'compliance_types',
        'countries',
        'currencies',
        'designations',
        'designation_has_permissions',
        'document_types',
        'email_templates',
        'event_sub_types',
        'event_types',
        'gl_accounts',
        'invoice_statuses',
        'job_types',
        'local_authorities',
        'local_authority_groups',
        'migrations',
        'nationalities',
        'note_types',
        'otp_configurations',
        'payment_methods',
        'permissions',
        'plans',
        'religious_places',
        'repair_categories',
        'roles',
        'role_has_permissions',
        'school_names',
        'sms_templates',
        'station_names',
        'sys_expense_categories',
        'sys_income_categories',
        'sys_bank_accounts',
        'sys_invoice_headers',
        'sys_taxes',
        'tax_rates',
        'tenancy_sub_statuses',
        'tenancy_types',
        'transaction_categories',
        'users_categories',
    ];

    /**
     * Classify a live table. New or uncertain tables deliberately fail closed to REVIEW.
     *
     * @return array{category: string, reason: string}
     */
    public function classify(string $table): array
    {
        if (in_array($table, self::CLEAR_TABLES, true)) {
            return [
                'category' => self::CLEAR,
                'reason' => $this->clearReason($table),
            ];
        }

        if (in_array($table, self::PRESERVE_TABLES, true)) {
            return [
                'category' => self::PRESERVE,
                'reason' => $this->preserveReason($table),
            ];
        }

        return [
            'category' => self::REVIEW,
            'reason' => 'Not in the reviewed table policy; purpose and dependencies require manual review.',
        ];
    }

    /** @return list<string> */
    public function configuredTables(): array
    {
        return array_values(array_merge(self::CLEAR_TABLES, self::PRESERVE_TABLES));
    }

    /** @return list<string> */
    public function clearTables(): array
    {
        return self::CLEAR_TABLES;
    }

    /** @return list<string> */
    public function preserveTables(): array
    {
        return self::PRESERVE_TABLES;
    }

    private function clearReason(string $table): string
    {
        return match ($table) {
            'users' => 'User/customer identities; delete non-Super Admins only.',
            'model_has_roles', 'model_has_permissions' => 'User authorization assignments; retain assignments belonging to Super Admins.',
            'accounts', 'account_users', 'account_subscriptions', 'account_subscription_addons' => 'SaaS customer/tenant account data.',
            'audits', 'gl_audit_logs', 'failed_jobs', 'cache', 'cache_locks', 'jobs', 'job_batches', 'sessions' => 'Operational, queue, cache, session, or log data.',
            'properties', 'tenancies', 'tenant_members', 'property_participants', 'property_responsibilities' => 'Property and tenancy business data.',
            'invoices', 'invoice_items', 'transactions', 'sys_payments', 'sys_receipts', 'sys_refunds', 'sys_purchase_invoices', 'sys_purchase_invoice_items', 'sys_sale_invoices', 'sys_sale_invoice_items' => 'Customer accounting transaction data.',
            default => 'Tenant, customer, business, transaction, document, or operational data.',
        };
    }

    private function preserveReason(string $table): string
    {
        return match ($table) {
            'migrations' => 'Laravel migration history required to manage the schema.',
            'roles', 'permissions', 'role_has_permissions', 'designations', 'designation_has_permissions' => 'System authorization definitions and role/designation permission configuration.',
            'plans', 'addons' => 'SaaS product catalogue/master configuration.',
            'business_settings', 'otp_configurations', 'email_templates', 'sms_templates' => 'System-wide application configuration or templates.',
            'gl_accounts', 'account_headers', 'sys_bank_accounts', 'sys_invoice_headers', 'sys_expense_categories', 'sys_income_categories', 'sys_taxes', 'tax_rates' => 'Accounting configuration or master data.',
            default => 'Lookup, reference, type, status, or other system-required master data.',
        };
    }
}
