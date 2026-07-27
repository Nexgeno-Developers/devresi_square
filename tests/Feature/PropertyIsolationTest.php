<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Rules\UniquePropertyIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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

    public function test_duplicate_property_identity_returns_a_validation_error_for_the_same_account(): void
    {
        $property = Property::create([
            'account_id' => 10,
            'line_1' => 'Flat 6',
            'line_2' => 'Discovery Dock Apartments East',
            'city' => 'London',
            'county' => 'London',
            'postcode' => 'E14 9RU',
            'country' => '1',
            'uprn' => '100000000001',
        ]);

        $duplicateAddress = [
            'line_1' => '  flat   6  ',
            'line_2' => 'Discovery Dock Apartments East',
            'city' => 'London',
            'county' => 'London',
            'postcode' => 'e14 9ru',
            'country' => '1',
            'uprn' => '100000000999',
        ];

        DB::table('properties')
            ->where('id', $property->id)
            ->update([
                'property_identity_hash' => hash('sha256', 'uprn:100000000001'),
            ]);

        $sameAccountValidator = Validator::make($duplicateAddress, [
            'line_1' => [
                new UniquePropertyIdentity($duplicateAddress, 10),
            ],
        ]);

        $this->assertTrue($sameAccountValidator->fails());
        $this->assertSame(
            'This property address already exists in your account.',
            $sameAccountValidator->errors()->first('line_1')
        );

        $otherAccountValidator = Validator::make($duplicateAddress, [
            'line_1' => [
                new UniquePropertyIdentity($duplicateAddress, 20),
            ],
        ]);

        $this->assertFalse($otherAccountValidator->fails());

        $currentPropertyValidator = Validator::make($duplicateAddress, [
            'line_1' => [
                new UniquePropertyIdentity($duplicateAddress, 10, $property->id),
            ],
        ]);

        $this->assertFalse($currentPropertyValidator->fails());

        $differentFlatAddress = [
            ...$duplicateAddress,
            'line_1' => 'Flat 4',
            'uprn' => '100000000001',
        ];
        $differentFlatValidator = Validator::make($differentFlatAddress, [
            'line_1' => [
                new UniquePropertyIdentity($differentFlatAddress, 10),
            ],
        ]);

        $this->assertFalse($differentFlatValidator->fails());
        $this->assertNotSame(
            Property::makeIdentityHash($duplicateAddress),
            Property::makeIdentityHash($differentFlatAddress)
        );

        $sameAddressWithDifferentLineSplit = [
            ...$duplicateAddress,
            'line_1' => 'Flat 6, Discovery Dock Apartments East',
            'line_2' => null,
        ];
        $differentLineSplitValidator = Validator::make($sameAddressWithDifferentLineSplit, [
            'line_1' => [
                new UniquePropertyIdentity($sameAddressWithDifferentLineSplit, 10),
            ],
        ]);

        $this->assertTrue($differentLineSplitValidator->fails());

        $property->delete();

        $softDeletedPropertyValidator = Validator::make($duplicateAddress, [
            'line_1' => [
                new UniquePropertyIdentity($duplicateAddress, 10),
            ],
        ]);

        $this->assertTrue($softDeletedPropertyValidator->fails());
    }

    public function test_identity_hash_migration_backfills_distinct_flats_without_colliding_on_legacy_duplicates(): void
    {
        $baseAddress = [
            'account_id' => 10,
            'line_2' => 'Discovery Dock Apartments East',
            'city' => 'London',
            'county' => 'London',
            'postcode' => 'E14 9RU',
            'country' => '1',
        ];

        DB::table('properties')->insert([
            [
                ...$baseAddress,
                'line_1' => 'Flat 6',
                'property_identity_hash' => hash('sha256', 'uprn:100000000001'),
            ],
            [
                ...$baseAddress,
                'line_1' => 'Flat 6',
                'property_identity_hash' => hash('sha256', 'uprn:100000000999'),
            ],
            [
                ...$baseAddress,
                'line_1' => 'Flat 4',
                'property_identity_hash' => hash('sha256', 'uprn:100000000004'),
            ],
        ]);

        $migration = require database_path(
            'migrations/2026_07_27_000001_rebuild_property_identity_hashes.php'
        );
        $migration->up();

        $flatSixHash = Property::makeIdentityHash([
            ...$baseAddress,
            'line_1' => 'Flat 6',
        ]);
        $flatFourHash = Property::makeIdentityHash([
            ...$baseAddress,
            'line_1' => 'Flat 4',
        ]);

        $this->assertSame(
            1,
            DB::table('properties')->where('property_identity_hash', $flatSixHash)->count()
        );
        $this->assertSame(
            1,
            DB::table('properties')->where('property_identity_hash', $flatFourHash)->count()
        );
        $this->assertNotSame($flatSixHash, $flatFourHash);
        $this->assertSame(3, DB::table('properties')->count());
    }

    public function test_permanent_deletion_releases_the_address_for_a_new_property(): void
    {
        $address = [
            'line_1' => 'Flat 4',
            'line_2' => 'Discovery Dock Apartments East',
            'city' => 'London',
            'county' => 'London',
            'postcode' => 'E14 9RU',
            'country' => '1',
        ];

        $property = Property::create([
            ...$address,
            'account_id' => 10,
        ]);
        $property->delete();

        $whileArchivedValidator = Validator::make($address, [
            'line_1' => [
                new UniquePropertyIdentity($address, 10),
            ],
        ]);

        $this->assertTrue($whileArchivedValidator->fails());

        $property->forceDelete();

        $afterPermanentDeleteValidator = Validator::make($address, [
            'line_1' => [
                new UniquePropertyIdentity($address, 10),
            ],
        ]);

        $this->assertFalse($afterPermanentDeleteValidator->fails());

        $replacement = Property::create([
            ...$address,
            'account_id' => 10,
        ]);

        $this->assertNotSame($property->id, $replacement->id);
        $this->assertSame(1, Property::query()->count());
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
            $table->string('line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->string('uprn')->nullable();
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
