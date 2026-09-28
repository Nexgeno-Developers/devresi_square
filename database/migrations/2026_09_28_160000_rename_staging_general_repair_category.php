<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repair_categories')) {
            return;
        }

        DB::table('repair_categories')
            ->where('name', 'Staging General Repair')
            ->update([
                'name' => 'General repair',
                'description' => 'General repair',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('repair_categories')) {
            return;
        }

        DB::table('repair_categories')
            ->where('name', 'General repair')
            ->where('description', 'General repair')
            ->update([
                'name' => 'Staging General Repair',
                'description' => 'General staging repair category.',
            ]);
    }
};
