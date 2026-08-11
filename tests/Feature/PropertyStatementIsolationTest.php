<?php

namespace Tests\Feature;

use App\Services\Accounting\StatementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyStatementIsolationTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.property_statement_isolation_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('property_statement_isolation_test');
        DB::setDefaultConnection('property_statement_isolation_test');

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('property_statement_isolation_test');
        DB::purge('property_statement_isolation_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_property_statement_excludes_ledger_lines_from_other_subscriber_accounts(): void
    {
        DB::table('gl_accounts')->insert([
            ['id' => 10, 'account_id' => 100, 'code' => '1100', 'name' => 'AR 100', 'type' => 'asset'],
            ['id' => 20, 'account_id' => 200, 'code' => '1100', 'name' => 'AR 200', 'type' => 'asset'],
        ]);
        DB::table('tenancies')->insert([
            ['id' => 1, 'account_id' => 100, 'property_id' => 500],
            ['id' => 2, 'account_id' => 200, 'property_id' => 600],
        ]);
        DB::table('tenant_members')->insert([
            ['account_id' => 100, 'tenancy_id' => 1, 'user_id' => 7],
            ['account_id' => 200, 'tenancy_id' => 2, 'user_id' => 7],
        ]);
        DB::table('gl_journals')->insert([
            ['id' => 1, 'date' => '2026-07-01', 'memo' => 'Current account', 'source_type' => 'invoice'],
            ['id' => 2, 'date' => '2026-07-01', 'memo' => 'Other account', 'source_type' => 'invoice'],
        ]);
        DB::table('gl_journal_lines')->insert([
            [
                'account_id' => 100,
                'gl_journal_id' => 1,
                'gl_account_id' => 10,
                'user_id' => 7,
                'debit' => 100,
                'credit' => 0,
            ],
            [
                'account_id' => 200,
                'gl_journal_id' => 2,
                'gl_account_id' => 20,
                'user_id' => 7,
                'debit' => 999,
                'credit' => 0,
            ],
        ]);

        $statement = app(StatementService::class)->propertyStatement(
            500,
            100,
            null,
            '2026-07-01',
            '2026-07-31'
        );

        $this->assertCount(1, $statement['lines']);
        $this->assertSame(100.0, $statement['closing']);
        $this->assertSame(100.0, $statement['summary']['invoiced']);
        $this->assertSame('Current account', $statement['lines']->first()['memo']);
    }

    private function createTestSchema(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value')->nullable();
        });
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('code');
            $table->string('name');
            $table->string('type');
        });
        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('property_id');
        });
        Schema::create('tenant_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenancy_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('sys_sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('link_to_type')->nullable();
            $table->unsignedBigInteger('link_to_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('gl_journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('memo')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
        });
        Schema::create('gl_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('gl_journal_id');
            $table->unsignedBigInteger('gl_account_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
        });
    }
}
