<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only. Records each step of a Resisquare desk repair.
     * Does not change repair, offer, or tenancy rows.
     */
    public function up(): void
    {
        if (Schema::hasTable('repair_desk_events')) {
            return;
        }

        Schema::create('repair_desk_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repair_desk_request_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('kind', 32);
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['repair_desk_request_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_desk_events');
    }
};
