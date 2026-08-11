<?php

namespace Tests\Unit;

use App\Models\OwnerGroup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerGroupTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.owner_group_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('owner_group_test');
        DB::setDefaultConnection('owner_group_test');

        Schema::create('owner_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('owner_group_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_group_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_main')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('owner_group_test');
        DB::purge('owner_group_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_active_for_user_only_returns_properties_from_active_assignments(): void
    {
        $activeGroupId = DB::table('owner_group')->insertGetId([
            'property_id' => 101,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $archivedGroupId = DB::table('owner_group')->insertGetId([
            'property_id' => 202,
            'status' => 'archived',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherGroupId = DB::table('owner_group')->insertGetId([
            'property_id' => 303,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('owner_group_users')->insert([
            [
                'owner_group_id' => $activeGroupId,
                'user_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_group_id' => $archivedGroupId,
                'user_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_group_id' => $otherGroupId,
                'user_id' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame(
            [101],
            OwnerGroup::query()->activeForUser(7)->pluck('property_id')->all()
        );
    }
}
