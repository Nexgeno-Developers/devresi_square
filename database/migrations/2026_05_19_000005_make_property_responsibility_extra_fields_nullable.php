<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_responsibilities', function (Blueprint $table) {
            if (Schema::hasColumn('property_responsibilities', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'designation_id')) {
                $table->foreignId('designation_id')->nullable()->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'commission_percentage')) {
                $table->decimal('commission_percentage', 5, 2)->nullable()->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_responsibilities', function (Blueprint $table) {
            if (Schema::hasColumn('property_responsibilities', 'branch_id')) {
                $table->foreignId('branch_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'designation_id')) {
                $table->foreignId('designation_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'commission_percentage')) {
                $table->decimal('commission_percentage', 5, 2)->nullable(false)->change();
            }
            if (Schema::hasColumn('property_responsibilities', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 2)->nullable(false)->change();
            }
        });
    }
};
