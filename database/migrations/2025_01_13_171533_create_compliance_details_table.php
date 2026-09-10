<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compliance_details')) {
            Schema::create('compliance_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compliance_record_id');
                $table->string('key');
                $table->string('value');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_details');
    }
};
