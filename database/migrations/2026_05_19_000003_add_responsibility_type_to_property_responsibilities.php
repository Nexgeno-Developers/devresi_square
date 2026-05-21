<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_responsibilities', function (Blueprint $table) {
            if (! Schema::hasColumn('property_responsibilities', 'responsibility_type')) {
                $table->string('responsibility_type')->nullable()->after('property_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_responsibilities', function (Blueprint $table) {
            if (Schema::hasColumn('property_responsibilities', 'responsibility_type')) {
                $table->dropColumn('responsibility_type');
            }
        });
    }
};
