<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'primary_email')) {
                $table->string('primary_email')->nullable()->after('emails');
            }
            if (! Schema::hasColumn('user_details', 'primary_phone')) {
                $table->string('primary_phone')->nullable()->after('phones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            foreach (['primary_email', 'primary_phone'] as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
