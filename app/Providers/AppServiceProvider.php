<?php

namespace App\Providers;

use OwenIt\Auditing\Auditor;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use Spatie\Permission\Models\Permission;

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
                return $user->hasPermissionTo($permission->name);
            });
        }
        
    }
}
