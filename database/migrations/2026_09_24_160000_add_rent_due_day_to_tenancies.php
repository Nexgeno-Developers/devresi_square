<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenancies', function (Blueprint $table) {
            if (! Schema::hasColumn('tenancies', 'rent_due_day')) {
                $table->unsignedTinyInteger('rent_due_day')->nullable()->after('frequency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenancies', function (Blueprint $table) {
            if (Schema::hasColumn('tenancies', 'rent_due_day')) {
                $table->dropColumn('rent_due_day');
            }
        });
    }
};
