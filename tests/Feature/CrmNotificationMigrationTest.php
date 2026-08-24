<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmNotificationMigrationTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.crm_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('crm_migration_test');
        DB::setDefaultConnection('crm_migration_test');
        $this->createPrerequisites();
    }

    protected function tearDown(): void
    {
        DB::disconnect('crm_migration_test');
        DB::purge('crm_migration_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    public function test_foundation_and_deadline_migrations_extend_an_existing_install(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id(); $table->string('receiver')->nullable(); $table->string('identifier');
            $table->string('email_type')->nullable(); $table->string('subject')->nullable();
            $table->text('default_text')->nullable(); $table->boolean('status')->default(true); $table->timestamps();
        });

        (require database_path('migrations/2026_08_13_000010_build_crm_notification_foundation.php'))->up();
        (require database_path('migrations/2026_08_13_000020_add_notification_deadline_fields.php'))->up();

        $this->assertTrue(Schema::hasColumns('notifications', ['account_id', 'event_key', 'category', 'priority', 'action_url']));
        $this->assertTrue(Schema::hasColumns('notification_logs', ['account_id', 'subject_type', 'subject_id', 'idempotency_key', 'scheduled_for']));
        $this->assertTrue(Schema::hasColumn('email_templates', 'account_id'));
        $this->assertTrue(Schema::hasColumn('accounts', 'timezone'));
        $this->assertTrue(Schema::hasTable('notification_preferences'));
        $this->assertTrue(Schema::hasTable('tenancy_notices'));
        $this->assertTrue(Schema::hasColumn('tenancies', 'deposit_received_at'));
        $this->assertTrue(Schema::hasColumn('tenant_members', 'right_to_rent_follow_up_due_at'));
        $this->assertTrue(Schema::hasColumn('compliance_records', 'remediation_due_at'));
        $this->assertTrue(Schema::hasColumn('repair_issues', 'acknowledged_at'));
    }

    private function createPrerequisites(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id(); $table->string('currency')->nullable(); $table->string('status')->nullable(); $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->string('type'); $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id'); $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id(); $table->string('identifier'); $table->string('notifiable_type'); $table->unsignedBigInteger('notifiable_id');
            $table->string('channel'); $table->string('recipient')->nullable(); $table->string('subject')->nullable();
            $table->text('message'); $table->json('payload')->nullable(); $table->string('status')->default('pending');
            $table->unsignedInteger('attempt')->default(0); $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable(); $table->timestamp('sent_at')->nullable(); $table->text('error')->nullable(); $table->timestamps();
        });
        foreach (['tenancies', 'tenant_members', 'compliance_records', 'repair_issues'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void { $table->id(); $table->timestamps(); });
        }
    }
}
