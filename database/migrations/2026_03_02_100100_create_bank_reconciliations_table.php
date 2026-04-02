<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sys_bank_account_id');
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2)->default(0);
            $table->decimal('gl_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->enum('status', ['draft', 'reconciled'])->default('draft');
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sys_bank_account_id')->references('id')->on('sys_bank_accounts')->cascadeOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('bank_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_reconciliation_id');
            $table->unsignedBigInteger('gl_journal_line_id')->nullable();
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_matched')->default(false);
            $table->timestamps();

            $table->foreign('bank_reconciliation_id')->references('id')->on('bank_reconciliations')->cascadeOnDelete();
            $table->foreign('gl_journal_line_id')->references('id')->on('gl_journal_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_lines');
        Schema::dropIfExists('bank_reconciliations');
    }
};
