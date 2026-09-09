<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountSubscriptionAddon;
use App\Models\AccountUser;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Staff;
use App\Models\User;
use App\Support\AccountMembership;
use Illuminate\Support\Facades\Schema;

class AccountLimitService
{
    private const SUBSCRIPTION_STATUSES = ['active', 'trialing', 'past_due'];

    public function getCurrentSubscription(Account $account): ?AccountSubscription
    {
        return $account->subscriptions()
            ->with('plan')
            ->whereIn('status', self::SUBSCRIPTION_STATUSES)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'trialing' THEN 1 WHEN 'past_due' THEN 2 ELSE 3 END")
            ->latest('created_at')
            ->first();
    }

    public function getPlan(Account $account): ?Plan
    {
        return $this->getCurrentSubscription($account)?->plan;
    }

    public function getPropertyLimit(Account $account): int
    {
        return $this->baseLimit($account, 'property_limit') + $this->addonGrant($account, 'property');
    }

    public function getBranchLimit(Account $account): int
    {
        return $this->baseLimit($account, 'branch_limit') + $this->addonGrant($account, 'branch');
    }

    public function getStaffLimit(Account $account): int
    {
        return $this->baseLimit($account, 'staff_limit') + $this->addonGrant($account, 'staff');
    }

    public function getPropertyManagerLimit(Account $account): int
    {
        return $this->baseLimit($account, 'property_manager_limit') + $this->addonGrant($account, 'property_manager');
    }

    public function getUsedProperties(Account $account): int
    {
        return Property::query()->where('account_id', $account->id)->count();
    }

    public function getUsedBranches(Account $account): int
    {
        return Branch::query()->where('account_id', $account->id)->count();
    }

    public function getUsedStaff(Account $account): int
    {
        return Staff::query()->where('account_id', $account->id)->count();
    }

    public function getUsedPropertyManagers(Account $account): int
    {
        // Count active account memberships, not per-property assignments, to avoid double counting.
        return AccountUser::query()
            ->where('account_id', $account->id)
            ->where('member_type', 'property_manager')
            ->where('status', 'active')
            ->count();
    }

    public function canAddProperty(Account $account): bool
    {
        return $this->canAddProperties($account, 1);
    }

    public function canAddProperties(Account $account, int $count = 1): bool
    {
        if ($count < 1) {
            return true;
        }

        return ($this->getUsedProperties($account) + $count) <= $this->getPropertyLimit($account);
    }

    public function canAddBranch(Account $account): bool
    {
        return $this->getUsedBranches($account) < $this->getBranchLimit($account);
    }

    public function canAddStaff(Account $account): bool
    {
        return $this->getUsedStaff($account) < $this->getStaffLimit($account);
    }

    public function canAddPropertyManager(Account $account): bool
    {
        return $this->getUsedPropertyManagers($account) < $this->getPropertyManagerLimit($account);
    }

    public function getPortalUserLimit(Account $account): int
    {
        $plan = $this->getPlan($account);

        if (! $plan) {
            return PHP_INT_MAX;
        }

        $columnLimit = Schema::hasColumn('plans', 'portal_user_limit')
            ? max(0, (int) ($plan->portal_user_limit ?? 0))
            : 0;

        if ($columnLimit > 0) {
            return $columnLimit;
        }

        return max(1, $this->getPropertyLimit($account));
    }

    public function getUsedPortalUsers(Account $account): int
    {
        return AccountUser::query()
            ->where('account_id', $account->id)
            ->whereIn('member_type', AccountMembership::PORTAL_TYPES)
            ->where('status', 'active')
            ->count();
    }

    public function canAddPortalUser(Account $account, ?User $user = null): bool
    {
        return $this->canAddPortalUsers($account, 1, $user);
    }

    public function canAddPortalUsers(Account $account, int $count = 1, ?User $user = null): bool
    {
        if ($count < 1) {
            return true;
        }

        if ($user) {
            $existing = AccountUser::query()
                ->where('account_id', $account->id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existing && AccountMembership::isPortalType($existing->member_type)) {
                return true;
            }
        }

        if (! $this->getPlan($account)) {
            return true;
        }

        return ($this->getUsedPortalUsers($account) + $count) <= $this->getPortalUserLimit($account);
    }

    public function canUseCompanyProfile(Account $account): bool
    {
        return $account->account_type !== 'landlord'
            && (bool) $this->getPlan($account)?->allow_company_profile;
    }

    public function canUseInvoiceBranding(Account $account): bool
    {
        return (bool) $this->getPlan($account)?->allow_invoice_branding;
    }

    public function canUseRolesPermissions(Account $account): bool
    {
        return $account->account_type !== 'landlord'
            && (bool) $this->getPlan($account)?->allow_roles_permissions;
    }

    public function canUseContactLogin(Account $account): bool
    {
        return (bool) $this->getPlan($account)?->allow_contact_login;
    }

    public function summary(Account $account): array
    {
        $subscription = $this->getCurrentSubscription($account);
        $plan = $subscription?->plan;

        return [
            'plan_name' => $plan?->name,
            'subscription_status' => $subscription?->status,
            'trial_ends_at' => $subscription?->trial_ends_at ?: $account->trial_ends_at,
            'properties' => $this->resourceSummary(
                $this->getUsedProperties($account),
                $this->getPropertyLimit($account),
                $this->canAddProperty($account)
            ),
            'branches' => $this->resourceSummary(
                $this->getUsedBranches($account),
                $this->getBranchLimit($account),
                $this->canAddBranch($account)
            ),
            'staff' => $this->resourceSummary(
                $this->getUsedStaff($account),
                $this->getStaffLimit($account),
                $this->canAddStaff($account)
            ),
            'property_managers' => $this->resourceSummary(
                $this->getUsedPropertyManagers($account),
                $this->getPropertyManagerLimit($account),
                $this->canAddPropertyManager($account)
            ),
            'portal_users' => $this->resourceSummary(
                $this->getUsedPortalUsers($account),
                $this->getPortalUserLimit($account),
                $this->canAddPortalUser($account)
            ),
            'features' => [
                'company_profile' => $this->canUseCompanyProfile($account),
                'invoice_branding' => $this->canUseInvoiceBranding($account),
                'roles_permissions' => $this->canUseRolesPermissions($account),
                'contact_login' => $this->canUseContactLogin($account),
            ],
        ];
    }

    private function baseLimit(Account $account, string $column): int
    {
        $plan = $this->getPlan($account);

        return $plan ? max(0, (int) $plan->{$column}) : 0;
    }

    private function addonGrant(Account $account, string $addonType): int
    {
        $subscription = $this->getCurrentSubscription($account);

        if (! $subscription) {
            return 0;
        }

        $granted = AccountSubscriptionAddon::query()
            ->where('account_subscription_addons.account_id', $account->id)
            ->where('account_subscription_addons.account_subscription_id', $subscription->id)
            ->where('account_subscription_addons.status', 'active')
            ->join('addons', 'addons.id', '=', 'account_subscription_addons.addon_id')
            ->where('addons.addon_type', $addonType)
            ->where('addons.is_active', true)
            ->selectRaw('COALESCE(SUM(account_subscription_addons.quantity * addons.grant_quantity), 0) as granted')
            ->value('granted');

        return (int) ($granted ?? 0);
    }

    private function resourceSummary(int $used, int $limit, bool $canAdd): array
    {
        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'can_add' => $canAdd,
        ];
    }
}
