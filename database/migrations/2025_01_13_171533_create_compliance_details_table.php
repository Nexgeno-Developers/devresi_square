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
        Schema::create('compliance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_record_id')->constrained('compliance_records')->onDelete('cascade'); // Cascade delete on compliance record
            $table->string('key');
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_details', function (Blueprint $table) {
            $table->dropForeign(['compliance_record_id']);
        });
        Schema::dropIfExists('compliance_details');
    }
};
