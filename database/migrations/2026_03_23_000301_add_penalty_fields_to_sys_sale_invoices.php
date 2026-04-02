<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->boolean('penalty_enabled')->default(false)->after('notes');

            $table->enum('penalty_type', ['percentage', 'flat_rate'])->nullable()->after('penalty_enabled');
            $table->decimal('penalty_fixed_rate', 15, 2)->nullable()->after('penalty_type');

            // Kept for record/display; calculation ignores this value.
            $table->decimal('penalty_amount_input', 15, 2)->nullable()->after('penalty_fixed_rate');

            $table->unsignedBigInteger('penalty_gl_account_id')->nullable()->after('penalty_amount_input');
            $table->foreign('penalty_gl_account_id')->references('id')->on('gl_accounts')->nullOnDelete();

            $table->integer('penalty_grace_days')->default(0)->after('penalty_gl_account_id');
            $table->decimal('penalty_max_amount', 15, 2)->nullable()->after('penalty_grace_days');

            $table->timestamp('penalty_applied_at')->nullable()->after('penalty_max_amount');
            $table->decimal('penalty_amount_applied', 15, 2)->nullable()->after('penalty_applied_at');

            $table->index(['penalty_enabled', 'penalty_applied_at'], 'sys_sale_invoices_penalty_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->dropIndex('sys_sale_invoices_penalty_idx');

            // Drop FK first
            $table->dropForeign(['penalty_gl_account_id']);

            $table->dropColumn([
                'penalty_enabled',
                'penalty_type',
                'penalty_fixed_rate',
                'penalty_amount_input',
                'penalty_gl_account_id',
                'penalty_grace_days',
                'penalty_max_amount',
                'penalty_applied_at',
                'penalty_amount_applied',
            ]);
        });
    }
};

