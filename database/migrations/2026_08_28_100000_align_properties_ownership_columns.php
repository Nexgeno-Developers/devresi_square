<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('quick_step')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('properties', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('properties', 'added_by')) {
            DB::table('properties')
                ->whereNull('created_by')
                ->whereNotNull('added_by')
                ->update(['created_by' => DB::raw('added_by')]);
        }
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (
                Schema::hasColumn('properties', 'created_by')
                && ! Schema::hasColumn('properties', 'added_by')
            ) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
