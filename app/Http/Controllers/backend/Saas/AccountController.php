<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Staff;
use App\Services\Saas\AccountLimitService;
use Illuminate\Http\Request;

class AccountController extends BaseSaasController
{
    private const ACCOUNT_TYPES = [
        'landlord',
        'estate_agent_freelance',
        'estate_agent_company',
    ];

    private const STATUSES = [
        'trialing',
        'active',
        'past_due',
        'suspended',
        'cancelled',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['account_type', 'status', 'plan_id', 'search']);

        $accounts = Account::query()
            ->with(['owner', 'currentSubscription.plan', 'latestSubscription.plan'])
            ->when($request->filled('account_type'), fn ($query) => $query->where('account_type', $request->account_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('plan_id'), function ($query) use ($request) {
                $query->whereHas('subscriptions', fn ($subscriptionQuery) => $subscriptionQuery->where('plan_id', $request->plan_id));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim($request->search) . '%';

                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('account_name', 'like', $search)
                        ->orWhere('billing_email', 'like', $search)
                        ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                            $ownerQuery
                                ->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $plans = Plan::query()->orderBy('name')->get();
        $accountTypes = self::ACCOUNT_TYPES;
        $statuses = self::STATUSES;

        return view('backend.saas.accounts.index', compact('accounts', 'plans', 'accountTypes', 'statuses', 'filters'));
    }

    public function show(Account $account)
    {
        $account->load([
            'owner',
            'currentSubscription.plan',
            'currentSubscription.activeAddons.addon',
            'latestSubscription.plan',
            'latestSubscription.activeAddons.addon',
            'accountUsers.user',
            'accountUsers.branch',
            'accountUsers.designation',
            'subscriptions.plan',
            'company',
        ]);

        $currentSubscription = $account->currentSubscription ?? $account->latestSubscription;
        $activeAddons = $currentSubscription?->activeAddons ?? collect();

        $limitSummary = app(AccountLimitService::class)->summary($account);
        $usedCounts = [
            'properties' => $limitSummary['properties']['used'],
            'branches' => $limitSummary['branches']['used'],
            'staff' => $limitSummary['staff']['used'],
            'property_managers' => $limitSummary['property_managers']['used'],
        ];

        $limitRows = $this->limitRows($currentSubscription);

        return view('backend.saas.accounts.show', compact(
            'account',
            'currentSubscription',
            'activeAddons',
            'usedCounts',
            'limitRows',
            'limitSummary'
        ));
    }

    private function limitRows($subscription): array
    {
        $plan = $subscription?->plan;

        $rows = [
            'property' => [
                'label' => 'Property limit',
                'base' => (int) ($plan?->property_limit ?? 0),
                'addons' => 0,
            ],
            'branch' => [
                'label' => 'Branch limit',
                'base' => (int) ($plan?->branch_limit ?? 0),
                'addons' => 0,
            ],
            'staff' => [
                'label' => 'Staff limit',
                'base' => (int) ($plan?->staff_limit ?? 0),
                'addons' => 0,
            ],
            'property_manager' => [
                'label' => 'Property manager limit',
                'base' => (int) ($plan?->property_manager_limit ?? 0),
                'addons' => 0,
            ],
        ];

        foreach (($subscription?->activeAddons ?? collect()) as $subscriptionAddon) {
            $type = $subscriptionAddon->addon?->addon_type;

            if (! isset($rows[$type])) {
                continue;
            }

            $rows[$type]['addons'] += (int) ($subscriptionAddon->quantity ?? 1) * (int) ($subscriptionAddon->addon?->grant_quantity ?? 0);
        }

        foreach ($rows as $key => $row) {
            $rows[$key]['total'] = $row['base'] + $row['addons'];
        }

        return $rows;
    }
}
