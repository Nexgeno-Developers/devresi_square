<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandlordPropertyWizardTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.landlord_wizard_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('landlord_wizard_test');
        DB::setDefaultConnection('landlord_wizard_test');

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('landlord_wizard_test');
        DB::purge('landlord_wizard_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_property_created_by_is_set_when_column_exists(): void
    {
        $property = Property::create([
            'account_id' => 1,
            'line_1' => '10 Downing Street',
            'city' => 'London',
            'postcode' => 'SW1A 2AA',
            'country' => '1',
            'created_by' => 42,
        ]);

        $this->assertSame(42, (int) $property->created_by);
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'created_by' => 42,
        ]);
    }

    public function test_added_by_backfill_populates_created_by(): void
    {
        DB::table('properties')->insert([
            'account_id' => 1,
            'line_1' => 'Flat 1',
            'city' => 'London',
            'postcode' => 'E1 1AA',
            'country' => '1',
            'added_by' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('properties')
            ->whereNull('created_by')
            ->whereNotNull('added_by')
            ->update(['created_by' => DB::raw('added_by')]);

        $this->assertDatabaseHas('properties', [
            'line_1' => 'Flat 1',
            'created_by' => 99,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('prop_ref_no')->nullable();
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode')->nullable();
            $table->string('property_type')->nullable();
            $table->string('specific_property_type')->nullable();
            $table->string('bedroom')->nullable();
            $table->string('bathroom')->nullable();
            $table->string('tenure')->nullable();
            $table->string('epc_rating')->nullable();
            $table->integer('quick_step')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('property_identity_hash')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
