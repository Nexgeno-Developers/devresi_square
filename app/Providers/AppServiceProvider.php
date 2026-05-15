<?php

namespace App\Providers;

use OwenIt\Auditing\Auditor;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use Spatie\Permission\Models\Permission;
use App\Models\SysSaleInvoice;
use App\Models\SysPurchaseInvoice;
use App\Models\User;
use App\Models\SysReceipt;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\Contractor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditorContract::class, Auditor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('components.frontend.form', 'form-component');
        Blade::component('components.backend.notes.notes', 'backend-notes-component');
        Blade::component('components.backend.documents.documents', 'backend-documents-component');
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();
        //Paginator::useBootstrap(); // Enables Bootstrap 4 styling

        // Super Admin check – this runs before all Gate checks
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
            // Option 1: check by email
            // return $user->email === 'superadmin@example.com' ? true : null;

            // Option 2: check by role
            // return $user->hasRole('Super Admin') ? true : null;
        });
        
        // $permissions = cache()->remember('all_permissions', 3600, fn() => Permission::all());
        $permissions = cache()->rememberForever('all_permissions', fn() => Permission::all());

        foreach ($permissions as $permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                if ($user->hasCustomizedStaffPermissions()) {
                    return $user->getDirectPermissions()->contains('name', $permission->name);
                }

                return $user->hasPermissionTo($permission->name)
                    || $user->hasDesignationPermission($permission->name);
            });
        }
        
        Relation::enforceMorphMap([
            // Short keys used in sale invoices (link_to_type, charge_to_type)
            'Property'   => Property::class,
            'Tenancy'    => Tenancy::class,
            'Owner'      => Owner::class,
            'Tenant'     => Tenant::class,
            'Contractor' => Contractor::class,
            // Short keys for accounting
            'sale_invoice'     => SysSaleInvoice::class,
            'purchase_invoice' => SysPurchaseInvoice::class,
            'receipt'          => SysReceipt::class,
            // Full class names — used by notes, documents, and other polymorphic relations
            'App\\Models\\User'       => User::class,
            'App\\Models\\Property'   => Property::class,
            'App\\Models\\Tenancy'    => Tenancy::class,
            'App\\Models\\Owner'      => Owner::class,
            'App\\Models\\Tenant'     => Tenant::class,
            'App\\Models\\Contractor' => Contractor::class,
            'App\\Models\\SysSaleInvoice'     => SysSaleInvoice::class,
            'App\\Models\\SysPurchaseInvoice' => SysPurchaseInvoice::class,
            'App\\Models\\SysReceipt'         => SysReceipt::class,
        ]);
    }
}
