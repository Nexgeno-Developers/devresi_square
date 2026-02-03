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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->default('');
            $table->string('name', 100)->default('');
            $table->integer('zone_id')->default(0);
            $table->tinyInteger('status')->default(1);
        
            // Auditing columns
            $table->string('created_by', 50)->default('');
            $table->string('updated_by', 50)->default('');
            $table->string('deleted_by', 50)->default('');
        
            $table->timestamps(); // adds created_at and updated_at
            $table->softDeletes(); // adds deleted_at as TIMESTAMP
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
