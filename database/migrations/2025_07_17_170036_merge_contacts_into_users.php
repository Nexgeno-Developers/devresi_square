<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            }
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'designation_id')) {
                $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('users_categories')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'selected_properties')) {
                $table->json('selected_properties')->nullable();
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 55)->nullable();
            }
            if (! Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name', 55)->nullable();
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 55)->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'address_line_1')) {
                $table->string('address_line_1', 255)->nullable();
            }
            if (! Schema::hasColumn('users', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable();
            }
            if (! Schema::hasColumn('users', 'postcode')) {
                $table->string('postcode', 15)->nullable();
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city', 55)->nullable();
            }
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country', 55)->nullable();
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(1)->comment('1 for active, 0 for inactive');
            }
            if (! Schema::hasColumn('users', 'can_login')) {
                $table->boolean('can_login')->default(false);
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 50)->nullable();
            }
            if (! Schema::hasColumn('users', 'quick_step')) {
                $table->integer('quick_step')->nullable();
            }
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (! Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
