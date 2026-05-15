<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('designation_has_permissions')) {
            Schema::create('designation_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('designation_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();

                $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
                $table->primary(['designation_id', 'permission_id'], 'designation_permission_primary');
            });
        }

        if (Schema::hasTable('staff') && Schema::hasTable('users') && Schema::hasTable('designations')) {
            $fallbackDesignationId = DB::table('designations')->where('title', 'Staff')->value('id');

            if (!$fallbackDesignationId) {
                $fallbackDesignationId = DB::table('designations')->insertGetId([
                    'title' => 'Staff',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasColumn('users', 'designation_id')) {
                $staffUserIdsWithoutDesignation = DB::table('staff')
                    ->join('users', 'staff.user_id', '=', 'users.id')
                    ->whereNull('users.designation_id')
                    ->pluck('users.id');

                if ($staffUserIdsWithoutDesignation->isNotEmpty()) {
                    DB::table('users')
                        ->whereIn('id', $staffUserIdsWithoutDesignation)
                        ->update([
                            'designation_id' => $fallbackDesignationId,
                            'updated_at' => now(),
                        ]);
                }
            }

            if (
                Schema::hasColumn('staff', 'role_id')
                && Schema::hasTable('role_has_permissions')
                && Schema::hasColumn('users', 'designation_id')
            ) {
                $permissionRows = DB::table('staff')
                    ->join('users', 'staff.user_id', '=', 'users.id')
                    ->join('role_has_permissions', 'staff.role_id', '=', 'role_has_permissions.role_id')
                    ->whereNotNull('users.designation_id')
                    ->select('users.designation_id', 'role_has_permissions.permission_id')
                    ->distinct()
                    ->get();

                foreach ($permissionRows as $permissionRow) {
                    DB::table('designation_has_permissions')->insertOrIgnore([
                        'designation_id' => $permissionRow->designation_id,
                        'permission_id' => $permissionRow->permission_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'role_id')) {
            $foreignKeys = DB::select("
                select constraint_name
                from information_schema.key_column_usage
                where table_schema = database()
                    and table_name = 'staff'
                    and column_name = 'role_id'
                    and referenced_table_name is not null
            ");

            foreach ($foreignKeys as $foreignKey) {
                $constraintName = str_replace('`', '``', $foreignKey->constraint_name);
                DB::statement("alter table `staff` drop foreign key `{$constraintName}`");
            }

            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('role_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'role_id')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable()->after('parent_id');
            });
        }

        Schema::dropIfExists('designation_has_permissions');
    }
};
