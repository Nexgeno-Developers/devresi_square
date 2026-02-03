<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // merge users into users
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Place these after 'id' for structural order
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->onDelete('set null');
            $table->foreignId('designation_id')->nullable()->after('branch_id')->constrained('designations')->onDelete('set null');

            // Add all user-related fields
            $table->foreignId('category_id')->nullable()->after('remember_token')->constrained('users_categories')->onDelete('set null');
            $table->json('selected_properties')->nullable();
            $table->string('first_name', 55)->nullable();
            $table->string('middle_name', 55)->nullable();
            $table->string('last_name', 55)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('postcode', 15)->nullable();
            $table->string('city', 55)->nullable();
            $table->string('country', 55)->nullable();
            $table->boolean('status')->default(1)->comment('1 for active, 0 for inactive');
            $table->boolean('can_login')->default(false);
            $table->integer('quick_step')->nullable();

            // Don't constrain these here
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
