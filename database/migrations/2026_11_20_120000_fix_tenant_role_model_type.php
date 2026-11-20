<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tenants created through "Quick add tenant" or onboarding were saved as a Tenant model, so Spatie
 * stored their role under the "Tenant" morph alias. The tenancy form looks roles up on User, so those
 * tenants could not be picked. Point the rows at App\Models\User (the code now creates plain Users).
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $key = config('permission.column_names.model_morph_key', 'model_id');
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $userType = \App\Models\User::class;

        // A user who already has the correct row would clash on the primary key: drop the stale copy.
        foreach (DB::table($table)->where('model_type', 'Tenant')->get() as $row) {
            $exists = DB::table($table)
                ->where($pivotRole, $row->{$pivotRole})
                ->where($key, $row->{$key})
                ->where('model_type', $userType)
                ->exists();

            if ($exists) {
                DB::table($table)
                    ->where($pivotRole, $row->{$pivotRole})
                    ->where($key, $row->{$key})
                    ->where('model_type', 'Tenant')
                    ->delete();
            }
        }

        DB::table($table)
            ->where('model_type', 'Tenant')
            ->update(['model_type' => $userType]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Data repair only: nothing to undo.
    }
};
