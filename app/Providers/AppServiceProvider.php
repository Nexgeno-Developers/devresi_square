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
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountSubscriptionAddon;
use App\Models\AccountUser;
use App\Models\Addon;
use App\Models\Event;
use App\Models\Plan;
use App\Models\PropertyParticipant;
use App\Models\Offer;
use App\Models\ComplianceRecord;
use App\Models\RepairIssue;
use App\Models\RentInvoice;
use App\Models\WorkOrder;
use App\Models\TenantMember;
use App\Models\TenancyNotice;
use App\Models\NotificationLog;
use App\Models\Document;
use App\Models\OwnerGroup;
use App\Observers\EventObserver;
use App\Policies\AccountUserPolicy;
use App\Policies\BillingPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FinancePolicy;
use App\Policies\OwnerGroupPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RepairIssuePolicy;
use App\Policies\TenancyPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditorContract::class, Auditor::class);

        $debugbarExplicit = env('DEBUGBAR_ENABLED');
        $debugbarOff = $this->app->environment('production')
            || $debugbarExplicit === false
            || $debugbarExplicit === 'false'
            || $debugbarExplicit === '0';

        if ($debugbarOff) {
            $this->app['config']->set('debugbar.enabled', false);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::observe(EventObserver::class);
        Blade::component('components.frontend.form', 'form-component');
        Blade::component('components.backend.notes.notes', 'backend-notes-component');
        Blade::component('components.backend.documents.documents', 'backend-documents-component');
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();
        //Paginator::useBootstrap(); // Enables Bootstrap 4 styling

        // Super Admin still bypasses Gates so platform screens keep working.
        // Customer records stay account-scoped in queries and policies for everyone else.
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Tenancy::class, TenancyPolicy::class);
        Gate::policy(RepairIssue::class, RepairIssuePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(OwnerGroup::class, OwnerGroupPolicy::class);
        Gate::policy(RentInvoice::class, FinancePolicy::class);
        Gate::policy(AccountUser::class, AccountUserPolicy::class);
        Gate::policy(Account::class, BillingPolicy::class);
        
        // $permissions = cache()->remember('all_permissions', 3600, fn() => Permission::all());
        $permissions = collect();

        try {
            if (Schema::hasTable('permissions')) {
                $permissions = cache()->rememberForever('all_permissions', fn() => Permission::all());
            }
        } catch (\Throwable $exception) {
            $isArtisanTest = $this->app->runningInConsole()
                && in_array('test', $_SERVER['argv'] ?? [], true);

            if (! $this->app->runningUnitTests() && ! $isArtisanTest) {
                throw $exception;
            }
        }

        foreach ($permissions as $permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasEffectivePermission($permission->name);
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
            'App\\Models\\Account'    => Account::class,
            'App\\Models\\AccountSubscription' => AccountSubscription::class,
            'App\\Models\\AccountSubscriptionAddon' => AccountSubscriptionAddon::class,
            'App\\Models\\AccountUser' => AccountUser::class,
            'App\\Models\\Addon'      => Addon::class,
            'App\\Models\\Event'      => Event::class,
            'App\\Models\\Plan'       => Plan::class,
            'App\\Models\\PropertyParticipant' => PropertyParticipant::class,
            'App\\Models\\Offer'      => Offer::class,
            'App\\Models\\ComplianceRecord' => ComplianceRecord::class,
            'App\\Models\\RepairIssue' => RepairIssue::class,
            'App\\Models\\WorkOrder' => WorkOrder::class,
            'App\\Models\\TenantMember' => TenantMember::class,
            'App\\Models\\TenancyNotice' => TenancyNotice::class,
            'App\\Models\\NotificationLog' => NotificationLog::class,
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
