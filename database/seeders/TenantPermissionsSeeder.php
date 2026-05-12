<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view properties',
            'view contacts',
            'view property repair',
            'create property repair',
            'view property owners',
            'view property tenancy',
        ];

        // Create any missing permissions first
        foreach ($permissions as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web']
            );
        }

        // Clear cache so newly created permissions are found
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::findByName('Tenant');
        $role->givePermissionTo($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Tenant permissions assigned: ' . implode(', ', $permissions));
    }
}
