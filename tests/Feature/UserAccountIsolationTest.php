<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccountIsolationTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.user_account_isolation_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('user_account_isolation_test');
        DB::setDefaultConnection('user_account_isolation_test');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
        });

        Schema::create('account_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('active');
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('user_account_isolation_test');
        DB::purge('user_account_isolation_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_users_are_limited_to_active_memberships_in_the_selected_account(): void
    {
        $firstOwnerId = DB::table('users')->insertGetId([
            'name' => 'First owner',
            'email' => 'first-owner@example.test',
        ]);
        $secondOwnerId = DB::table('users')->insertGetId([
            'name' => 'Second owner',
            'email' => 'second-owner@example.test',
        ]);
        $inactiveOwnerId = DB::table('users')->insertGetId([
            'name' => 'Inactive owner',
            'email' => 'inactive-owner@example.test',
        ]);

        DB::table('account_users')->insert([
            ['account_id' => 10, 'user_id' => $firstOwnerId, 'status' => 'active'],
            ['account_id' => 20, 'user_id' => $secondOwnerId, 'status' => 'active'],
            ['account_id' => 10, 'user_id' => $inactiveOwnerId, 'status' => 'inactive'],
        ]);

        $this->assertSame(
            [$firstOwnerId],
            User::forAccount(10)->pluck('id')->all()
        );
        $this->assertSame(
            [$secondOwnerId],
            User::forAccount(20)->pluck('id')->all()
        );
    }

    public function test_missing_account_context_fails_closed(): void
    {
        DB::table('users')->insert([
            'name' => 'Unscoped owner',
            'email' => 'unscoped-owner@example.test',
        ]);

        $this->assertFalse(User::forAccount(null)->exists());
    }
}
