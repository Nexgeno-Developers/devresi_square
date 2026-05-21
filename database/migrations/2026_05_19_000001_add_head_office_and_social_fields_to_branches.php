<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'is_main_head_office')) {
                $table->boolean('is_main_head_office')->default(false)->after('company_id');
            }
        });

    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_main_head_office')) {
                $table->dropColumn('is_main_head_office');
            }
        });
    }
};
