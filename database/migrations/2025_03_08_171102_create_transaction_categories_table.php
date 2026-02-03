<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable(); // short key: RENT, GAS, EPC, MAINT
            $table->boolean('is_income')->default(true); // true = income, false = expense
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true); // for system use and can't be deleted
            $table->timestamps();
        });

        // Optionally, insert default transaction categories
        DB::table('transaction_categories')->insert([
            ['name' => 'Salary', 'code' => 'SALARY', 'is_income' => true],
            ['name' => 'Advance Payment', 'code' => 'ADVANCE', 'is_income' => true],
            ['name' => 'Utility', 'code' => 'UTILITY', 'is_income' => false],
            ['name' => 'Electricity Bill', 'code' => 'ELECTRICITY', 'is_income' => false],
            ['name' => 'Travel', 'code' => 'TRAVEL', 'is_income' => false],
            ['name' => 'Other', 'code' => 'OTHER', 'is_income' => false],
            ['name' => 'Rent', 'code' => 'RENT', 'is_income' => true],
            ['name' => 'Deposit', 'code' => 'DEPOSIT', 'is_income' => true],
            ['name' => 'Gas Bill', 'code' => 'GAS', 'is_income' => false],
            ['name' => 'EPC Certificate', 'code' => 'EPC', 'is_income' => false],
            ['name' => 'Maintenance', 'code' => 'MAINT', 'is_income' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
