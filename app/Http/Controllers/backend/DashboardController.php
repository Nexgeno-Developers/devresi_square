<?php

namespace App\Http\Controllers\Backend;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountUser;
use App\Models\Branch;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Registration;
use App\Models\Staff;
use App\Models\Tenancy;
use App\Models\WorkOrder;
use App\Models\RepairIssue;
use App\Services\Saas\AccountLimitService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Tenants don't have a dashboard — send them to their home page
        if ($user->hasRole('Tenant')) {
            return redirect()->route('backend.home');
        }

        $this->authorize('view dashboard');
        // $this->middleware(middleware: 'auth'); // Ensure the user is authenticated
        // $this->middleware('can:view dashboard'); // Optional: Ensure the user has permission to view the dashboard

        if (!$user->hasAnyRole(['Super Admin', 'Landlord', 'Staff', 'Property Manager', 'Estate Agent', 'Test'])) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        $planUsageAccount = current_account();
        $accountId = $planUsageAccount?->id;

        $usersCount = AccountUser::where('account_id', $accountId)->where('status', 'active')->count();
        $propertiesCount = Property::forAccount($accountId)->count();
        $invoicesCount = Invoice::whereHas('workOrder', fn ($query) => $query->forAccount($accountId))->count();
        $workOrdersCount = WorkOrder::forAccount($accountId)->count();
        $repairIssuesCount = RepairIssue::forAccount($accountId)->count();
        $activeTenanciesCount = Tenancy::forAccount($accountId)->where('status', 'Active')->count();
        $openRepairsCount = RepairIssue::forAccount($accountId)->whereNotIn('status', ['Closed', 'Invoice Paid'])->count();
        $branchesCount = Branch::forAccount($accountId)->count();
        $staffCount = Staff::forAccount($accountId)->where('status', 'active')->count();

        $recentProperties = Property::forAccount($accountId)->latest()->limit(5)->get();
        $recentRepairs = RepairIssue::forAccount($accountId)
            ->with('property')
            ->latest()
            ->limit(5)
            ->get();
        $recentTenancies = Tenancy::forAccount($accountId)
            ->with('property')
            ->latest()
            ->limit(5)
            ->get();

        $dashboardRole = match (true) {
            $user->hasRole('Landlord') => 'landlord',
            $user->hasRole('Estate Agent') => 'estate_agent',
            $user->hasRole('Property Manager') => 'property_manager',
            $user->hasRole('Staff') => 'staff',
            default => 'account',
        };

        $planUsageSummary = $planUsageAccount
            ? app(AccountLimitService::class)->summary($planUsageAccount)
            : null;

        return view('backend.dashboard', compact(
            // 'users',
            'usersCount',
            'propertiesCount',
            'invoicesCount',
            'workOrdersCount',
            'repairIssuesCount',
            'activeTenanciesCount',
            'openRepairsCount',
            'branchesCount',
            'staffCount',
            'recentProperties',
            'recentRepairs',
            'recentTenancies',
            'dashboardRole',
            'planUsageAccount',
            'planUsageSummary'
        ));
    }

    private function superAdminDashboard()
    {
        $revenueSubscriptions = AccountSubscription::query()
            ->with(['activeAddons'])
            ->whereIn('status', ['active', 'past_due'])
            ->get();

        $mrrMinor = $revenueSubscriptions->sum(function (AccountSubscription $subscription) {
            $planAmount = (int) $subscription->price_at_signup_minor;
            $planMrr = $subscription->billing_cycle === 'annual' ? $planAmount / 12 : $planAmount;

            $addonMrr = $subscription->activeAddons->sum(function ($addon) {
                $amount = (int) $addon->price_at_purchase_minor * max(1, (int) $addon->quantity);

                return $addon->billing_cycle === 'annual' ? $amount / 12 : $amount;
            });

            return $planMrr + $addonMrr;
        });

        $accountStatusCounts = Account::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAccounts = (int) $accountStatusCounts->sum();
        $saasMetrics = [
            'total_accounts' => $totalAccounts,
            'new_accounts_this_month' => Account::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'active_accounts' => (int) ($accountStatusCounts['active'] ?? 0),
            'trial_accounts' => (int) ($accountStatusCounts['trialing'] ?? 0),
            'past_due_accounts' => (int) ($accountStatusCounts['past_due'] ?? 0),
            'pending_approvals' => Registration::where('status', 'verified')->count(),
            'active_plans' => Plan::where('is_active', true)->count(),
            'mrr' => $mrrMinor / 100,
            'arr' => ($mrrMinor * 12) / 100,
        ];

        $recentAccounts = Account::query()
            ->with(['owner', 'currentSubscription.plan', 'latestSubscription.plan'])
            ->latest()
            ->limit(6)
            ->get();

        $expiringTrials = AccountSubscription::query()
            ->with(['account.owner', 'plan'])
            ->where('status', 'trialing')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)->endOfDay()])
            ->orderBy('trial_ends_at')
            ->limit(6)
            ->get();

        $planDistribution = Plan::query()
            ->withCount(['subscriptions as live_subscriptions_count' => function ($query) {
                $query->whereIn('status', ['trialing', 'active', 'past_due']);
            }])
            ->orderByDesc('live_subscriptions_count')
            ->orderBy('name')
            ->get();

        $recentRegistrations = Registration::query()
            ->with('plan')
            ->whereIn('status', ['verified', 'approved', 'rejected'])
            ->latest()
            ->limit(6)
            ->get();

        return view('backend.dashboard', compact(
            'saasMetrics',
            'accountStatusCounts',
            'totalAccounts',
            'recentAccounts',
            'expiringTrials',
            'planDistribution',
            'recentRegistrations'
        ));
    }

}
