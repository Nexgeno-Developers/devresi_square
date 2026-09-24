<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'visible_to_tenant')) {
                $table->boolean('visible_to_tenant')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'visible_to_tenant')) {
                $table->dropColumn('visible_to_tenant');
            }
        });
    }
};
