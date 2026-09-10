<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\GlAccountSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed lookup data. SaaS demo accounts are created separately via
     * `php artisan saas:create-staging-test-data`.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            TenantPermissionsSeeder::class,
            UserSeeder::class,
            NoteTypeSeeder::class,
            DesignationSeeder::class,
            StationNamesTableSeeder::class,
            SchoolNamesTableSeeder::class,
            ReligiousPlacesTableSeeder::class,
            UserCategorySeeder::class,
            CurrencySeeder::class,
            ComplianceTypeSeeder::class,
            JobTypesSeeder::class,
            LocalAuthoritySeeder::class,
            CountrySeeder::class,
            NationalitySeeder::class,
            EventTypeSeeder::class,
            TenancyTypeSeeder::class,
            TenancySubStatusSeeder::class,
            GlAccountSeeder::class,
            SysAccountingSeeder::class,
            CrmNotificationTemplatesSeeder::class,
            SaasPlanSeeder::class,
        ]);
    }
}
