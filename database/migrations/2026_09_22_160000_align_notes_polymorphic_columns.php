<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            if (! Schema::hasColumn('notes', 'noteable_type')) {
                $table->string('noteable_type')->nullable()->after('account_id');
            }
            if (! Schema::hasColumn('notes', 'noteable_id')) {
                $table->unsignedBigInteger('noteable_id')->nullable()->after('noteable_type');
            }
            if (! Schema::hasColumn('notes', 'note_type_id')) {
                $table->unsignedBigInteger('note_type_id')->nullable()->after('noteable_id');
            }
        });

        if (Schema::hasColumn('notes', 'property_id')) {
            DB::table('notes')
                ->whereNull('noteable_type')
                ->whereNotNull('property_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('notes')->where('id', $row->id)->update([
                            'noteable_type' => \App\Models\Property::class,
                            'noteable_id' => $row->property_id,
                        ]);
                    }
                });
        }

        if (Schema::hasColumn('notes', 'type') && Schema::hasTable('note_types')) {
            $types = DB::table('note_types')->pluck('id', 'name');
            foreach ($types as $name => $id) {
                DB::table('notes')
                    ->whereNull('note_type_id')
                    ->where('type', $name)
                    ->update(['note_type_id' => $id]);
            }

            $fallback = DB::table('note_types')->orderBy('id')->value('id');
            if ($fallback) {
                DB::table('notes')->whereNull('note_type_id')->update(['note_type_id' => $fallback]);
            }
        }

        // Soften legacy NOT NULL columns so polymorphic creates can omit them.
        try {
            if (Schema::hasColumn('notes', 'property_id')) {
                DB::statement('ALTER TABLE notes MODIFY property_id BIGINT UNSIGNED NULL');
            }
            if (Schema::hasColumn('notes', 'user_id')) {
                DB::statement('ALTER TABLE notes MODIFY user_id BIGINT UNSIGNED NULL');
            }
            if (Schema::hasColumn('notes', 'type')) {
                DB::statement('ALTER TABLE notes MODIFY type VARCHAR(50) NULL');
            }
        } catch (\Throwable $e) {
            // SQLite / non-MySQL: leave as-is; inserts may still set legacy columns from callers.
        }

        Schema::table('notes', function (Blueprint $table) {
            try {
                $table->index(['noteable_type', 'noteable_id'], 'notes_noteable_index');
            } catch (\Throwable $e) {
            }
            try {
                $table->index(['note_type_id'], 'notes_note_type_id_index');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            try {
                $table->dropIndex('notes_noteable_index');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('notes_note_type_id_index');
            } catch (\Throwable $e) {
            }

            foreach (['note_type_id', 'noteable_id', 'noteable_type'] as $col) {
                if (Schema::hasColumn('notes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
