<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('prefix', 20)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->unique(['document_type','branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
