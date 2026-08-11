<?php

namespace Tests\Unit;

use App\Models\Transaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    private string $originalDatabaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseConnection = DB::getDefaultConnection();

        config([
            'database.connections.transaction_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('transaction_test');
        DB::setDefaultConnection('transaction_test');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('transaction_test');
        DB::purge('transaction_test');
        DB::setDefaultConnection($this->originalDatabaseConnection);

        parent::tearDown();
    }

    public function test_total_amount_is_populated_from_amount_when_transaction_is_created(): void
    {
        $transaction = Transaction::create(['amount' => 200]);

        $this->assertEqualsWithDelta(0, (float) $transaction->fresh()->tax_amount, 0.001);
        $this->assertEqualsWithDelta(200, (float) $transaction->fresh()->total_amount, 0.001);
    }

    public function test_total_amount_stays_in_sync_when_amount_is_updated(): void
    {
        $transaction = Transaction::create(['amount' => 200]);

        $transaction->update(['amount' => 325.50]);

        $this->assertEqualsWithDelta(325.50, (float) $transaction->fresh()->total_amount, 0.001);
    }
}
