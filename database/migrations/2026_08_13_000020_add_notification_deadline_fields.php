<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenancies', function (Blueprint $table) {
            $table->dateTime('deposit_received_at')->nullable();
            $table->dateTime('deposit_protected_at')->nullable();
            $table->dateTime('prescribed_information_sent_at')->nullable();
            $table->dateTime('written_terms_sent_at')->nullable();
        });
        Schema::table('tenant_members', function (Blueprint $table) {
            $table->boolean('right_to_rent_required')->default(false);
            $table->dateTime('right_to_rent_checked_at')->nullable();
            $table->dateTime('right_to_rent_follow_up_due_at')->nullable();
        });
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->unsignedBigInteger('responsible_user_id')->nullable();
            $table->dateTime('remediation_due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
        });
        Schema::table('repair_issues', function (Blueprint $table) {
            $table->dateTime('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
        });
        Schema::create('tenancy_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenancy_id');
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('notice_type');
            $table->dateTime('served_at');
            $table->dateTime('effective_at')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('status')->default('served');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'tenancy_id']);
            $table->index(['effective_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_notices');
        Schema::table('repair_issues', fn (Blueprint $table) => $table->dropColumn(['acknowledged_at', 'acknowledged_by']));
        Schema::table('compliance_records', fn (Blueprint $table) => $table->dropColumn(['responsible_user_id', 'remediation_due_at', 'completed_at']));
        Schema::table('tenant_members', fn (Blueprint $table) => $table->dropColumn(['right_to_rent_required', 'right_to_rent_checked_at', 'right_to_rent_follow_up_due_at']));
        Schema::table('tenancies', fn (Blueprint $table) => $table->dropColumn(['deposit_received_at', 'deposit_protected_at', 'prescribed_information_sent_at', 'written_terms_sent_at']));
    }
};
