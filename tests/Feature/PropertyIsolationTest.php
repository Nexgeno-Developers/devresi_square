<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyIsolationTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.property_isolation_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('property_isolation_test');
        DB::setDefaultConnection('property_isolation_test');

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('property_isolation_test');
        DB::purge('property_isolation_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_property_is_unique_for_one_subscription_user_but_available_to_another(): void
    {
        $firstSubscription = $this->createSubscribedUser('first@example.test');
        $secondSubscription = $this->createSubscribedUser('second@example.test');

        $address = [
            'line_1' => 'Flat 60, Discovery Dock Apartments East',
            'postcode' => 'E14 9RU',
            'country' => 'United Kingdom',
        ];

        $firstProperty = Property::create([
            ...$address,
            'account_id' => $firstSubscription['account_id'],
            'created_by' => $firstSubscription['user_id'],
        ]);

        $duplicateWasRejected = false;

        try {
            Property::create([
                ...$address,
                'account_id' => $firstSubscription['account_id'],
                'created_by' => $firstSubscription['user_id'],
            ]);
        } catch (QueryException) {
            $duplicateWasRejected = true;
        }

        $this->assertTrue(
            $duplicateWasRejected,
            'The same subscription user was able to add the same property twice.'
        );

        $secondProperty = Property::create([
            ...$address,
            'account_id' => $secondSubscription['account_id'],
            'created_by' => $secondSubscription['user_id'],
        ]);

        $this->assertSame($firstProperty->property_identity_hash, $secondProperty->property_identity_hash);
        $this->assertNotSame($firstProperty->account_id, $secondProperty->account_id);
        $this->assertSame(2, Property::query()->count());
    }

    /**
     * @return array{user_id: int, account_id: int}
     */
    private function createSubscribedUser(string $email): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => $email,
            'email' => $email,
        ]);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $userId,
            'status' => 'active',
        ]);

        DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'status' => 'active',
        ]);

        return [
            'user_id' => $userId,
            'account_id' => $accountId,
        ];
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('status');
        });

        Schema::create('account_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('status');
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('line_1')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->string('property_identity_hash', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['account_id', 'property_identity_hash'],
                'properties_account_identity_unique'
            );
        });
    }
}
