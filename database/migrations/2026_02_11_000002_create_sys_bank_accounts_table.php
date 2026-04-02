<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('sort_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->string('branch')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('account_type')->nullable();
            $table->string('purpose')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['savings', 'current', 'overdraft'])->default('savings');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_bank_accounts');
    }
};
