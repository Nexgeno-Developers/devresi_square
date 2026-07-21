<?php

namespace Tests\Unit;

use App\Support\DatabaseCleanupTablePolicy;
use PHPUnit\Framework\TestCase;

class DatabaseCleanupTablePolicyTest extends TestCase
{
    public function test_current_policy_has_114_unique_explicit_tables(): void
    {
        $tables = (new DatabaseCleanupTablePolicy)->configuredTables();

        $this->assertCount(114, $tables);
        $this->assertCount(114, array_unique($tables));
    }

    public function test_unknown_tables_fail_closed_to_review(): void
    {
        $classification = (new DatabaseCleanupTablePolicy)->classify('future_unreviewed_table');

        $this->assertSame(DatabaseCleanupTablePolicy::REVIEW, $classification['category']);
        $this->assertNotSame('', $classification['reason']);
    }

    public function test_super_admin_sensitive_tables_are_clear_with_selective_reasons(): void
    {
        $policy = new DatabaseCleanupTablePolicy;

        foreach (['users', 'model_has_roles', 'model_has_permissions'] as $table) {
            $classification = $policy->classify($table);

            $this->assertSame(DatabaseCleanupTablePolicy::CLEAR, $classification['category']);
            $this->assertStringContainsString('Super Admin', $classification['reason']);
        }
    }

    public function test_configuration_tables_requested_for_preservation_are_preserved(): void
    {
        $policy = new DatabaseCleanupTablePolicy;

        foreach (['designations', 'designation_has_permissions', 'sys_bank_accounts'] as $table) {
            $this->assertSame(DatabaseCleanupTablePolicy::PRESERVE, $policy->classify($table)['category']);
        }
    }
}
