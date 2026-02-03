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
        Schema::create('owner_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_group_id')->constrained('owner_group')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable(); // Ensure it is nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null'); // Manual foreign key definition
            $table->boolean('is_main')->default(false);
            $table->timestamps();

            // Add soft delete timestamp column
            $table->softDeletes();

            // Add 'added_by', 'updated_by', 'deleted_by' columns
            $table->unsignedBigInteger('added_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)
            $table->unsignedBigInteger('updated_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)

            // Add foreign key constraints for 'added_by', 'updated_by', 'deleted_by'
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_group', function (Blueprint $table) {
            // Drop foreign key constraints before dropping the table
            $table->dropForeign(['owner_group_id']);
            $table->dropForeign(['user_id']);

            $table->dropForeign(['added_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            // Drop the added, updated, and deleted columns
            $table->dropColumn('added_by');
            $table->dropColumn('updated_by');
            $table->dropColumn('deleted_by');

            // Drop the soft deletes column
            $table->dropSoftDeletes();

        });

        Schema::dropIfExists('owner_group_users');
    }
};
