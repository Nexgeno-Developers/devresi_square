<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->integer('ref_no');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('ownergroup_id')->nullable(); // Make sure this matches the 'id' column in owner_group table
            $table->string('description', 550);
            $table->tinyInteger('paid_by_landlord')->default(0);
            $table->tinyInteger('managed_by_property')->default(0);
            $table->integer('charge_landlord')->nullable();
            $table->integer('tax')->default(0);
            $table->decimal('schedule_charge', 10, 0)->nullable();
            $table->longText('attachment');
            $table->string('type');
            $table->date('due_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 10, 0);
            $table->string('frequency');
            $table->string('status');
            $table->unsignedBigInteger('added_by');
            $table->timestamps();

            // Foreign key reference to properties table
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->onDelete('cascade');  // Optional: set to cascade or restrict as needed

            // Foreign key reference to owner_group table for ownergroup_id
            $table->foreign('ownergroup_id')
                ->references('id')
                ->on('owner_group') // Ensure this table and column exists and matches the type
                ->onDelete('set null');  // Optional: set to cascade, restrict, or set null depending on the need

            $table->foreign('added_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // or set null, restrict
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('charges', function (Blueprint $table) {
            // Drop foreign key constraints before dropping the columns
            $table->dropForeign(['property_id']);
            $table->dropForeign(['ownergroup_id']); // Drop the foreign key reference for ownergroup_id
        });

        Schema::dropIfExists('charges');
    }
};
