<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_months')->default(60);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            $table->enum('status', ['active', 'disposed', 'fully_depreciated'])->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('gl_asset_account_id')->nullable();
            $table->unsignedBigInteger('gl_depreciation_account_id')->nullable();
            $table->unsignedBigInteger('gl_expense_account_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('gl_asset_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->foreign('gl_depreciation_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->foreign('gl_expense_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
