<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_members', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_members', 'details_status')) {
                $table->string('details_status', 32)->default('pending');
            }
            if (! Schema::hasColumn('tenant_members', 'details_confirmed_at')) {
                $table->timestamp('details_confirmed_at')->nullable();
            }
        });

        Schema::create('tenancy_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('tenancy_id')->index();
            $table->unsignedBigInteger('tenant_member_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->text('message');
            $table->json('fields')->nullable();
            $table->json('snapshot')->nullable();
            $table->text('landlord_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('tenancy_id')->references('id')->on('tenancies')->cascadeOnDelete();
            $table->foreign('tenant_member_id')->references('id')->on('tenant_members')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_correction_requests');

        Schema::table('tenant_members', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_members', 'details_confirmed_at')) {
                $table->dropColumn('details_confirmed_at');
            }
            if (Schema::hasColumn('tenant_members', 'details_status')) {
                $table->dropColumn('details_status');
            }
        });
    }
};
