<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('repair_issues', 'landlord_note')) {
                $table->string('landlord_note', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_issues', function (Blueprint $table) {
            if (Schema::hasColumn('repair_issues', 'landlord_note')) {
                $table->dropColumn('landlord_note');
            }
        });
    }
};
