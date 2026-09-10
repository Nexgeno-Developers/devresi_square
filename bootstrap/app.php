<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\EnsureTokenIsValid;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sale-invoices:generate-recurring')->dailyAt('00:05');
        $schedule->command('crm-notifications:send-due')->everyMinute()->withoutOverlapping();
        $schedule->command('notifications:retry')->everyFiveMinutes();
        $schedule->command('events:send-reminders')->everyMinute()->withoutOverlapping();
        $schedule->command('sale-invoices:apply-penalties')->dailyAt('00:10');
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Append global middleware
        $middleware->append(EnsureTokenIsValid::class);

        // Laravel 11 already registers the standard web middleware group. Configure
        // its CSRF middleware directly so third-party webhooks can post safely.
        $middleware->validateCsrfTokens(except: [
            'backend/trumbowyg/upload',
            'stripe/webhook',
            'stripe/rent/webhook',
        ]);

        // Define middleware for the API group
        $middleware->prependToGroup('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Other middleware for the API group can be added here
        ]);

        // Define middleware aliases
        $middleware->alias([
            'subscribed' => EnsureTokenIsValid::class,
            // Route middleware aliases
            // 'role' => \App\Http\Middleware\RoleMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            // 'password.confirm' => \Illuminate\Auth\Middleware\EnsureFrontendRequestsAreStateful::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'permission' => PermissionMiddleware::class,
            'role'       => RoleMiddleware::class,
            'current.account' => \App\Http\Middleware\EnsureCurrentAccount::class,
            'property.participant' => \App\Http\Middleware\EnsurePropertyParticipantAccess::class,
            'not.portal' => \App\Http\Middleware\DenyPortalUsers::class,
            'account.status' => \App\Http\Middleware\AccountStatusGuard::class,
            'landlord.restricted' => \App\Http\Middleware\RestrictLandlordRoutes::class,
            'tenancy.manage' => \App\Http\Middleware\RestrictTenancyManagement::class,
            'portal.tenant' => \App\Http\Middleware\EnsureTenantPortal::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling can be configured here
    })
    ->create();
