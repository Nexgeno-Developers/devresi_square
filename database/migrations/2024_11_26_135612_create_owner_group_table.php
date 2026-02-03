<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('owner_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id'); // Foreign key referencing properties table (unsignedBigInteger)
            $table->date('purchased_date');
            $table->date('sold_date')->nullable();
            $table->date('archived_date')->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamps();

            // Add soft delete timestamp column
            $table->softDeletes();

            // Add 'added_by' and 'updated_by' columns
            $table->unsignedBigInteger('added_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)
            $table->unsignedBigInteger('updated_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)

            // Add deleted_by column to track who deleted the record
            $table->unsignedBigInteger('deleted_by')->nullable(); // Foreign key referencing users table (unsignedBigInteger)

            // Add the foreign key constraint for property_id
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');

            // Add the foreign key constraint for deleted_by (if you have a users table)
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Add foreign key constraints for 'added_by', 'updated_by'
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }


    /**
     * Reverse the migrations.
     */

    public function down()
    {
        Schema::table('owner_group', function (Blueprint $table) {

            // Drop foreign key constraints before dropping the table
            $table->dropForeign(['property_id']);

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

        Schema::dropIfExists('owner_group');
    }
};
