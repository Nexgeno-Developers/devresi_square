<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only. Creates the Resisquare repair desk table when it is missing.
     * Does not seed rows, and does not change repair, offer, or tenancy data.
     */
    public function up(): void
    {
        if (Schema::hasTable('repair_desk_requests')) {
            return;
        }

        Schema::create('repair_desk_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('repair_issue_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status', 32)->default('requested');
            $table->text('landlord_note')->nullable();
            $table->text('team_note')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['repair_issue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_desk_requests');
    }
};
