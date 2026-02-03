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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // polymorphic owner (user, product, complianceRecord, etc.)
            $table->unsignedBigInteger('documentable_id');
            $table->string('documentable_type');
            $table->index(['documentable_type','documentable_id']);
            
            // reference to your Upload record (actual file metadata)
            $table->string('upload_ids', 2000)->nullable();
            
            // optional categorization
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
