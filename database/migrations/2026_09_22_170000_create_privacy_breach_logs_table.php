<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('privacy_breach_logs')) {
            return;
        }

        Schema::create('privacy_breach_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->string('severity', 32)->default('low'); // low|medium|high|critical
            $table->string('status', 32)->default('open'); // open|contained|closed
            $table->string('summary', 255);
            $table->text('details')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_breach_logs');
    }
};
