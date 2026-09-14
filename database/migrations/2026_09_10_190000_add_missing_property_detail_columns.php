<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'epc_required')) {
                $table->boolean('epc_required')->nullable();
            }

            if (! Schema::hasColumn('properties', 'youtube_url')) {
                $table->text('youtube_url')->nullable();
            }

            if (! Schema::hasColumn('properties', 'instagram_url')) {
                $table->text('instagram_url')->nullable();
            }

            if (! Schema::hasColumn('properties', 'nearest_places')) {
                $table->json('nearest_places')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            foreach (['epc_required', 'youtube_url', 'instagram_url', 'nearest_places'] as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
