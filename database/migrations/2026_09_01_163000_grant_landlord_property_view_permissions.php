<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view dashboard',
            'view properties',
            'create properties',
            'edit properties',
            'delete properties',
            'view property owners',
            'view property tenancy',
            'view property documents',
            'view tenants',
            'create tenants',
            'edit tenants',
            'view documents',
            'view rent payments',
            'view maintenance requests',
            'view communication log',
            'view own lease info',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep granted permissions; removing them would lock existing landlords out again.
    }
};
