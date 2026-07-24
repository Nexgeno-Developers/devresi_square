<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Models\Account;
use App\Services\Saas\AccountLimitService;

trait EnforcesSaasPlanLimits
{
    protected function currentLimitAccount(): ?Account
    {
        return current_account();
    }

    protected function bypassesSaasPlanLimits(): bool
    {
        return auth()->user()?->hasRole('Super Admin') === true;
    }

    protected function saasLimitError(string $limit): ?string
    {
        if ($this->bypassesSaasPlanLimits()) {
            return null;
        }

        $account = $this->currentLimitAccount();

        if (! $account) {
            return 'No active SaaS subscription was found for this account.';
        }

        $limits = app(AccountLimitService::class);

        return match ($limit) {
            'property' => $limits->canAddProperty($account)
                ? null
                : 'Your current plan has reached the property limit. Please upgrade your plan or buy an extra property addon.',
            'branch' => $limits->canAddBranch($account)
                ? null
                : 'Your current plan does not allow more branches. Please upgrade your plan or buy an extra branch addon.',
            'staff' => $limits->canAddStaff($account)
                ? null
                : 'Your current plan has reached the staff limit. Please upgrade your plan or buy an extra staff addon.',
            'property_manager' => $limits->canAddPropertyManager($account)
                ? null
                : 'Your current plan has reached the property manager limit. Please buy a property manager addon or upgrade your plan.',
            'company_profile' => $limits->canUseCompanyProfile($account)
                ? null
                : 'Your current plan does not include estate agency company profile access.',
            'invoice_branding' => $limits->canUseInvoiceBranding($account)
                ? null
                : 'Your current plan does not include invoice branding.',
            'roles_permissions' => $limits->canUseRolesPermissions($account)
                ? null
                : 'Your current plan does not include staff roles and permissions.',
            'contact_login' => $limits->canUseContactLogin($account)
                ? null
                : 'Your current plan does not include contact portal login access.',
            default => null,
        };
    }

    protected function abortIfSaasLimitDenied(string $limit): void
    {
        if ($message = $this->saasLimitError($limit)) {
            abort(403, $message);
        }
    }

    protected function redirectIfSaasLimitDenied(string $limit, string $route)
    {
        if ($message = $this->saasLimitError($limit)) {
            flash($message)->error();

            return redirect()->route($route);
        }

        return null;
    }

    protected function backIfSaasLimitDenied(string $limit)
    {
        if ($message = $this->saasLimitError($limit)) {
            flash($message)->error();

            return back()->withInput();
        }

        return null;
    }
}
