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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->foreignId('category_id')->constrained('users_categories')->onDelete('set null');
            $table->json('selected_properties')->nullable();
            $table->string('first_name', 55)->nullable();
            $table->string('middle_name', 55)->nullable();
            $table->string('last_name', 55)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 55)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('postcode', 15)->nullable();
            $table->string('city', 55)->nullable();
            $table->string('country', 55)->nullable();            
            // Change the type to unsignedBigInteger and keep it nullable
            $table->unsignedBigInteger('country_id')->nullable()->change();
            // Add foreign key constraint
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            $table->boolean('status')->default(1)->comment('1 for active, 0 for inactive');
            $table->integer('quick_step')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();

            $table->foreignId('company_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->onDelete('set null');
            $table->foreignId('designation_id')->nullable()->after('branch_id')->constrained()->onDelete('set null');

            $table->timestamps();
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->integer('country_id')->unsigned()->nullable()->change();
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropForeign(['designation_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
