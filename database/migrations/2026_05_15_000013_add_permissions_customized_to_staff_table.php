<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'permissions_customized')) {
            Schema::table('staff', function (Blueprint $table) {
                $column = $table->boolean('permissions_customized')->default(false);

                if (Schema::hasColumn('staff', 'parent_id')) {
                    $column->after('parent_id');
                } elseif (Schema::hasColumn('staff', 'user_id')) {
                    $column->after('user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'permissions_customized')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('permissions_customized');
            });
        }
    }
};
