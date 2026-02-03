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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade'); // Foreign key referencing the property
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key referencing the user
            $table->text('content');  // Content of the note (this will hold the HTML from Quill)
            $table->enum('type', ['Email', 'Call', 'Text', 'General', 'MIS']);  // Type of note
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
