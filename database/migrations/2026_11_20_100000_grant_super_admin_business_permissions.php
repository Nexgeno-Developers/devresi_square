<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Super Admins are now bound by their role's permissions instead of bypassing every permission.
// Grant the role the business permissions it was missing so current admins keep the same access;
// narrower admins can be made by giving them a role or direct grants without these.
return new class extends Migration
{
    private const PERMISSIONS = [
        'view tenancies', 'create tenancies', 'edit tenancies', 'delete tenancies',
        'view contacts', 'create property repair', 'manage registrations',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        $this->forgetPermissionCaches();
    }

    public function down(): void
    {
        // Intentionally left as is: removing grants could lock admins out of screens.
        $this->forgetPermissionCaches();
    }

    private function forgetPermissionCaches(): void
    {
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            cache()->forget('all_permissions');
        } catch (\Throwable) {
            // Cache may be unavailable during install; permissions reload on next request.
        }
    }
};
