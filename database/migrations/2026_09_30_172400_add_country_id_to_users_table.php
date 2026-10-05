<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'country_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('country_id')
                    ->nullable()
                    ->after('country')
                    ->constrained('countries')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'country_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('country_id');
            });
        }
    }
};
