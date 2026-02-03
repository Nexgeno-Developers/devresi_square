<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Owner',
            'Property Manager',
            'Tenant',
            'Landlord',
            'Estate Agent',
            'Agent',
            'Contractor',
            'Maintenance',
            'Service Provider',
            'User',
            'Letting Applicant',
            'Sales Applicant',
            'Solicitor',
            'Other',
            'Staff',
        ];
        
        // 2. Define permissions, grouped by module
        $permissions = [
            // Dashboard & System
            'view dashboard',
            'access settings',
            'manage roles & permissions',
            'manage users',
            'view own profile',
            'view office profiles',
            'view all profiles',

            // Properties
            'view properties',
            'create properties',
            'edit properties',
            'delete properties',
            'assign properties to landlord',

            // Tenancies
            'view tenants',
            'create tenants',
            'edit tenants',
            'delete tenants',
            'assign tenants to property',
            'end tenancy',
            'view own lease info',

            // Maintenance
            'view maintenance requests',
            'create maintenance requests',
            'assign maintenance tasks',
            'update maintenance status',
            'complete maintenance tasks',

            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage user categories',

            // Documents
            'upload documents',
            'view documents',
            'download documents',
            'delete documents',

            // Finance
            'view rent payments',
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'mark invoice paid',
            'view reports',

            // Communication
            'send notifications',
            'view communication log',

            'view all staffs',
            'add staff',
            'edit staff',
            'delete staff',
            
            'view staff roles',
            'add staff role',
            'edit staff role',
            'delete staff role',
            
        ];

        // 3. Create permissions
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 4. Create roles & assign permissions
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            switch ($roleName) {
                case 'Super Admin':
                    // All permissions
                    $role->syncPermissions(Permission::all());
                    break;

                case 'Owner':
                    $role->syncPermissions([
                        'view dashboard',
                        'view properties',
                        'view tenants',
                        'view rent payments',
                        'view reports',
                        'view own profile',
                    ]);
                    break;

                case 'Landlord':
                    $role->syncPermissions([
                        'view dashboard',

                        'view properties',
                        'create properties',
                        'edit properties',
                        'delete properties',
                        'assign properties to landlord',

                        'view tenants',
                        'create tenants',
                        'edit tenants',
                        'delete tenants',
                        'assign tenants to property',

                        'view maintenance requests',
                        'view rent payments',
                        'view documents',
                        'view communication log',
                        'view own lease info',
                    ]);
                    break;

                case 'Estate Agent':
                    $role->syncPermissions([
                        'view dashboard',
                        'manage users',
                        'view office profiles',

                        'view properties',
                        'create properties',
                        'edit properties',
                        'delete properties',
                        'assign properties to landlord',

                        'view tenants',
                        'create tenants',
                        'edit tenants',
                        'delete tenants',
                        'assign tenants to property',

                        'view maintenance requests',
                        'create maintenance requests',
                        'view documents',
                        'view communication log',
                    ]);
                    break;

                case 'Property Manager':
                    $role->syncPermissions([
                        'view dashboard',
                        'manage users',
                        'view all profiles',

                        'view properties',
                        'create properties',
                        'edit properties',
                        'delete properties',
                        'assign properties to landlord',

                        'view tenants',
                        'create tenants',
                        'edit tenants',
                        'delete tenants',
                        'assign tenants to property',

                        'view maintenance requests',
                        'create maintenance requests',
                        'assign maintenance tasks',
                        'update maintenance status',
                        'complete maintenance tasks',

                        'upload documents',
                        'view documents',
                        'download documents',
                        'delete documents',

                        'view rent payments',
                        'create invoices',
                        'edit invoices',
                        'mark invoice paid',

                        'view users',
                        'manage user categories',
                        'view reports',

                        'send notifications',
                        'view communication log',
                    ]);
                    break;

                // case 'Finance Manager':
                //     $role->syncPermissions([
                //         'view dashboard',
                //         'view rent payments',
                //         'create invoices',
                //         'edit invoices',
                //         'mark invoice paid',
                //         'view reports',
                //     ]);
                //     break;

                // case 'Maintenance Coordinator':
                //     $role->syncPermissions([
                //         'view dashboard',
                //         'view maintenance requests',
                //         'create maintenance requests',
                //         'assign maintenance tasks',
                //         'update maintenance status',
                //         'complete maintenance tasks',
                //         'view users',
                //     ]);
                //     break;

                case 'Contractor':
                    $role->syncPermissions([
                        'view maintenance requests',
                        'update maintenance status',
                        'complete maintenance tasks',
                        'view own lease info',
                    ]);
                    break;

                case 'Tenant':
                    $role->syncPermissions([
                        'view dashboard',
                        'view own profile',
                        'view own lease info',
                        'create maintenance requests',
                        'view maintenance requests',
                        'view documents',
                        'upload documents',
                        'view communication log',
                    ]);
                    break;
                case 'Agent':
                case 'Maintenance':
                case 'Service Provider':
                case 'User':
                case 'Letting Applicant':
                case 'Sales Applicant':
                case 'Solicitor':
                case 'Other':
                case 'Staff':
                    // keep your existing assignments, e.g. Tenant, Finance, etc.
                    // or leave empty if you’ll seed them elsewhere
                    break;
            }
        }
    }
}
