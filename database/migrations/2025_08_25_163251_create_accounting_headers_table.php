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
        Schema::create('account_headers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Rent, Maintenance, Commission
            $table->text('description')->nullable(); // Header Description
            
            $table->enum('charge_on', ['property', 'tenancy', 'landlord', 'contractor', 'applicants', 'all']);
            $table->enum('who_can_view', ['tenant', 'owner', 'contractor', 'everyone'])->default('everyone');
            $table->boolean('reminders')->default(false);
            $table->boolean('agent_fees')->default(false);
            $table->boolean('require_bank_details')->default(false);

            $table->enum('charge_in', ['arrears', 'advance', 'anytime'])->default('anytime');
            $table->boolean('can_have_duration')->default(false);
            $table->enum('settle_through', ['credit_note', 'debit_note', 'refund', 'all'])->default('all');
            $table->boolean('duration_parameter_required')->default(false);

            $table->enum('penalty_type', ['percentage', 'flat_rate'])->nullable();
            $table->boolean('tax_included')->default(false);
            $table->enum('tax_type', ['percentage', 'flat_rate'])->nullable();

            $table->enum('transaction_between', [
                'tenant_landlord', 
                'landlord_agent', 
                'agent_contractor', 
                'internal_staff_agent', 
                'landlord_contractor', 
                'landlord_management', 
                'all'
            ])->default('all');
            
            // Accrue
            $table->boolean('accrue')->default(false)->after('transaction_between');

            // Bank reference (nullable, in case no bank is required)
            $table->unsignedBigInteger('bank_id')->nullable()->after('accrue');
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('set null');

            // Penalty Calculation
            $table->enum('penalty_frequency', ['annual','monthly'])->nullable()->after('bank_id');

            // Penalty Due
            $table->enum('penalty_due_type', ['instant','days_after_invoice','custom'])->nullable()->after('penalty_frequency');
            $table->integer('penalty_due_days')->nullable()->after('penalty_due_type');

            // Tenancy Period Inclusion
            $table->boolean('include_tenancy_period')->default(false)->after('penalty_due_days');

            // Metadata
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_headers');
    }
};
