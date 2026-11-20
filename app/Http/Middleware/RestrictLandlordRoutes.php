<?php

namespace App\Http\Middleware;

use App\Support\AccountMembership;
use App\Support\AccountType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictLandlordRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        // Platform settings and shared lookups belong to Super Admin, whatever the caller's role or account.
        if ($routeName !== '' && $this->platformOnly($routeName, $request)) {
            abort(403, 'This area is only available to Resisquare administrators.');
        }

        // Account email templates are for people who run the workspace, not its tenants or contractors.
        if ($this->matchesPrefix($routeName, 'email-templates.') && $this->isPortalMember($user)) {
            abort(403);
        }

        if ($routeName !== '' && $this->contractorDeskBlocked($user, $routeName)) {
            abort(403, 'This area is not available on the contractor account.');
        }

        if ($this->onLandlordAccount($user) && $routeName !== '' && $this->isBlocked($routeName)) {
            if (! ($routeName === 'customer.statements' && $this->isTenantMember($user))) {
                abort(403, 'This area is not available on the landlord plan.');
            }
        }

        if (! $this->usesLandlordAllowlist($user)) {
            return $next($request);
        }

        if ($routeName === '' || ! $this->isAllowed($routeName)) {
            abort(403, 'This area is not available on the landlord plan.');
        }

        return $next($request);
    }

    private function onLandlordAccount($user): bool
    {
        if ($this->estateAgentOnLandlordAccount($user)) {
            return true;
        }

        $account = function_exists('current_account') ? current_account() : null;
        $membership = function_exists('current_account_membership') ? current_account_membership($user) : null;

        return $account
            && $membership
            && AccountType::isLandlord($account->account_type);
    }

    private function isTenantMember($user): bool
    {
        $membership = function_exists('current_account_membership') ? current_account_membership($user) : null;

        return $membership && AccountMembership::isTenant($membership->member_type);
    }

    private function isPortalMember($user): bool
    {
        $membership = function_exists('current_account_membership') ? current_account_membership($user) : null;

        return $user->hasRole('Contractor') || $user->hasRole('Tenant')
            || ($membership && (AccountMembership::isTenant($membership->member_type) || $membership->member_type === AccountMembership::CONTRACTOR));
    }

    private function usesLandlordAllowlist($user): bool
    {
        if (! $this->onLandlordAccount($user)) {
            return false;
        }

        $membership = function_exists('current_account_membership') ? current_account_membership($user) : null;

        if ($membership && AccountMembership::isTenant($membership->member_type)) {
            return false;
        }

        if ($membership && $membership->member_type === AccountMembership::CONTRACTOR) {
            return false;
        }

        return true;
    }

    private function estateAgentOnLandlordAccount($user): bool
    {
        $account = function_exists('current_account') ? current_account() : null;

        return $account
            && AccountType::isLandlord($account->account_type)
            && $user->hasRole('Estate Agent');
    }

    private function contractorDeskBlocked($user, string $routeName): bool
    {
        if (! $user->hasRole('Contractor')) {
            return false;
        }
        if (function_exists('sees_landlord_portfolio') && sees_landlord_portfolio($user)) {
            return false;
        }
        if (function_exists('is_tenant_portal_user') && is_tenant_portal_user($user)) {
            return false;
        }

        foreach ([
            'admin.properties.',
            'backend.dashboard',
            'backend.home',
            'tenant.',
            'admin.finance.',
            'admin.offers.',
            'admin.tenancies.',
            'backend.billing.',
        ] as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Fully platform-only: settings that change the whole platform, or tables with no account scope. */
    private const PLATFORM_ROUTES = [
        'otp.', 'sms-templates.', 'smtp_settings', 'business_settings', 'env_key_update', 'test.smtp',
        'backend.transactions.', 'backend.transaction_categories.', 'backend.account_headers.',
        'admin.estate-charges.', 'admin.estate-charges-items.',
        // The double-entry accounting module does not scope GL, journals or reports by account yet,
        // so it stays with Resisquare staff until it does. Landlord rent and finance use admin.finance.*.
        'backend.accounting.',
    ];

    /** Shared lookups: anyone signed in may read them, only Super Admin may change them. */
    private const PLATFORM_LOOKUP_ROUTES = [
        'admin.document-types.', 'admin.note-types.', 'admin.job_types.', 'admin.tenancy_types.',
        'admin.tenancy_sub_statuses.', 'backend.event_types.', 'backend.event_sub_types.', 'user-categories.',
    ];

    private function platformOnly(string $routeName, Request $request): bool
    {
        foreach (self::PLATFORM_ROUTES as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return true;
            }
        }

        $readOnly = in_array($request->method(), ['GET', 'HEAD'], true)
            && ! preg_match('/\.(create|edit|destroy|delete)$/', $routeName);

        foreach (self::PLATFORM_LOOKUP_ROUTES as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return ! $readOnly;
            }
        }

        return false;
    }

    private function isBlocked(string $routeName): bool
    {
        foreach (config('landlord_mvp.blocked_route_name_prefixes', []) as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowed(string $routeName): bool
    {
        if ($this->isBlocked($routeName)) {
            return false;
        }

        foreach (config('landlord_mvp.enabled_route_name_prefixes', []) as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPrefix(string $routeName, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        if ($routeName === $prefix) {
            return true;
        }

        if (str_ends_with($prefix, '.')) {
            return str_starts_with($routeName, $prefix);
        }

        return str_starts_with($routeName, $prefix.'.');
    }
}
