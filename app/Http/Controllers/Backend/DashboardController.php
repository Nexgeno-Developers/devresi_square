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
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\ComplianceRecord;
use App\Services\Saas\AccountLimitService;
use App\Support\AccountMembership;
use App\Support\CrmLanguage;
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

        app(\App\Services\Onboarding\LandlordOnboardingService::class)
            ->syncOverlaySessionForPage($user, current_account(), false);

        // Tenants don't have a dashboard — send them to their home page
        if (is_tenant_portal_user($user)) {
            return redirect()->route('backend.home');
        }

        if (! $user->hasRole('Landlord') && ! is_landlord_plan_user($user)) {
            $this->authorize('view dashboard');
        }

        if (! $user->hasAnyRole(['Super Admin', 'Landlord', 'Staff', 'Property Manager', 'Estate Agent', 'Test'])
            && ! is_landlord_plan_user($user)
        ) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        $planUsageAccount = current_account();
        $accountId = $planUsageAccount?->id;

        $usersCount = AccountUser::where('account_id', $accountId)->where('status', 'active')->count();
        if (($user->hasRole('Landlord') || is_landlord_plan_user($user)) && $accountId) {
            $usersCount = AccountUser::query()
                ->where('account_id', $accountId)
                ->where('status', 'active')
                ->where('member_type', AccountMembership::TENANT)
                ->count();
        }
        $propertiesCount = Property::forAccount($accountId)->count();
        $invoicesCount = Invoice::whereHas('workOrder', fn ($query) => $query->forAccount($accountId))->count();
        $workOrdersCount = WorkOrder::forAccount($accountId)->count();
        $repairIssuesCount = RepairIssue::forAccount($accountId)->count();
        $activeTenanciesCount = Tenancy::forAccount($accountId)
            ->where('status', 'Active')
            ->whereHas('property')
            ->count();
        $openRepairsCount = RepairIssue::forAccount($accountId)->open()->count();
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
            : $this->emptyPlanUsageSummary();

        $pendingCorrectionCount = 0;
        $pendingCorrectionTenancyId = null;
        $overdueRentCount = 0;
        $complianceAttentionCount = 0;
        $complianceAttentionPropertyId = null;
        $depositAttentionCount = 0;
        $depositAttentionTenancyId = null;
        if ($accountId && \Illuminate\Support\Facades\Schema::hasTable('tenancy_correction_requests')) {
            $confirmation = app(\App\Services\Portal\TenancyDetailsConfirmationService::class);
            $pendingCorrectionCount = $confirmation->pendingCountForAccount($accountId);
            $pendingCorrectionTenancyId = $confirmation->latestPendingForAccount($accountId)?->tenancy_id;
        }
        if ($accountId && \Illuminate\Support\Facades\Schema::hasTable('rent_invoices')) {
            $overdueRentCount = app(\App\Services\Finance\RentFinanceService::class)->overdueOpenCount((int) $accountId);
        }
        if ($accountId && \Illuminate\Support\Facades\Schema::hasTable('compliance_records')) {
            $attention = \App\Models\ComplianceRecord::certificateGapsForAccount((int) $accountId);
            $complianceAttentionCount = count($attention);
            $complianceAttentionPropertyId = $attention[0]['property']->id ?? null;
        }
        if ($accountId && \Illuminate\Support\Facades\Schema::hasTable('tenancies')) {
            $depositAttention = \App\Models\Tenancy::needingDepositProtectionForAccount((int) $accountId);
            $depositAttentionCount = $depositAttention->count();
            $depositAttentionTenancyId = $depositAttention->first()?->id;
        }

        $crmDashboard = is_landlord_plan_user($user)
            ? $this->landlordCrmDashboard($user, $accountId, $propertiesCount, $openRepairsCount, $complianceAttentionCount)
            : null;

        return view('backend.dashboard', [
            'crmDashboard' => $crmDashboard,
            'usersCount' => $usersCount,
            'propertiesCount' => $propertiesCount,
            'invoicesCount' => $invoicesCount,
            'workOrdersCount' => $workOrdersCount,
            'repairIssuesCount' => $repairIssuesCount,
            'activeTenanciesCount' => $activeTenanciesCount,
            'openRepairsCount' => $openRepairsCount,
            'branchesCount' => $branchesCount,
            'staffCount' => $staffCount,
            'recentProperties' => $recentProperties,
            'recentRepairs' => $recentRepairs,
            'recentTenancies' => $recentTenancies,
            'dashboardRole' => $dashboardRole,
            'planUsageAccount' => $planUsageAccount,
            'planUsageSummary' => $planUsageSummary,
            'pendingCorrectionCount' => $pendingCorrectionCount,
            'pendingCorrectionTenancyId' => $pendingCorrectionTenancyId,
            'overdueRentCount' => $overdueRentCount,
            'complianceAttentionCount' => $complianceAttentionCount,
            'complianceAttentionPropertyId' => $complianceAttentionPropertyId,
            'depositAttentionCount' => $depositAttentionCount,
            'depositAttentionTenancyId' => $depositAttentionTenancyId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function landlordCrmDashboard($user, ?int $accountId, int $propertiesCount, int $openRepairsCount, int $complianceAttentionCount): array
    {
        $occupied = 0;
        $notice = 0;
        $received = 0.0;
        $outstanding = 0.0;
        $actions = [];

        if ($accountId) {
            $occupied = (int) Tenancy::forAccount($accountId)
                ->where('status', 'Active')
                ->whereNotNull('property_id')
                ->where('property_id', '>', 0)
                ->distinct()
                ->count('property_id');
            $notice = (int) Tenancy::forAccount($accountId)
                ->where('status', 'Inactive')
                ->whereNotNull('property_id')
                ->distinct()
                ->count('property_id');

            if (\Illuminate\Support\Facades\Schema::hasTable('rent_payments')) {
                $received = (float) RentPayment::forAccount($accountId)
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount');
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('rent_invoices')) {
                $outstanding = (float) RentInvoice::forAccount($accountId)
                    ->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
                    ->where('balance', '>', 0)
                    ->sum('balance');

                $overdue = RentInvoice::forAccount($accountId)
                    ->with('property')
                    ->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
                    ->where('balance', '>', 0)
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->orderBy('due_date')
                    ->first();
                if ($overdue) {
                    $days = $overdue->due_date ? (int) $overdue->due_date->startOfDay()->diffInDays(now()->startOfDay()) : 0;
                    $address = $overdue->property?->line_1 ?: 'A property';
                    $actions[] = [
                        'text' => $address.' — '.rs_money($overdue->balance).' outstanding · '.$days.' '.($days === 1 ? 'day' : 'days').' overdue',
                        'url' => route('admin.finance.index', ['status' => 'overdue']),
                        'cta' => 'View Rent',
                    ];
                }
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('compliance_records')) {
                $gap = ComplianceRecord::certificateGapsForAccount((int) $accountId)[0] ?? null;
                if ($gap) {
                    $address = $gap['property']->line_1 ?: 'A property';
                    $name = $gap['type']->name ?: 'Compliance item';
                    $expiry = $gap['record']?->expiry_date;
                    if ($expiry && $expiry->isFuture()) {
                        $days = (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay());
                        $text = $address.' — '.$name.' expires in '.$days.' '.($days === 1 ? 'day' : 'days').'.';
                    } elseif ($expiry) {
                        $text = $address.' — '.$name.' has expired.';
                    } else {
                        $text = $address.' — '.$name.' is missing.';
                    }
                    $actions[] = [
                        'text' => $text,
                        'url' => route('admin.compliance.index'),
                        'cta' => 'Book Inspection',
                    ];
                }
            }

            $repair = RepairIssue::forAccount($accountId)->with('property')->open()->latest('id')->first();
            if ($repair) {
                $address = $repair->property?->line_1 ?: 'A property';
                $title = \Illuminate\Support\Str::limit($repair->description ?: 'Repair', 80);
                $actions[] = [
                    'text' => $address.' — '.$title.' reported by tenant.',
                    'url' => route('admin.property_repairs.show', $repair->id),
                    'cta' => 'Assign Contractor',
                ];
            }
        }

        $vacant = max(0, $propertiesCount - $occupied);
        $occupancy = $propertiesCount > 0 ? (int) round(($occupied / $propertiesCount) * 100) : 0;

        return [
            'greeting' => CrmLanguage::greeting($user->name),
            'properties' => $propertiesCount,
            'occupied' => $occupied,
            'notice' => $notice,
            'vacant' => $vacant,
            'occupancy' => $occupancy,
            'received' => $received,
            'outstanding' => $outstanding,
            'open_repairs' => $openRepairsCount,
            'compliance_ok' => $complianceAttentionCount === 0,
            'actions' => $actions,
        ];
    }

    private function emptyPlanUsageSummary(): array
    {
        $emptyResource = [
            'used' => 0,
            'limit' => 0,
            'remaining' => 0,
            'can_add' => false,
        ];

        return [
            'plan_name' => null,
            'subscription_status' => null,
            'trial_ends_at' => null,
            'properties' => $emptyResource,
            'branches' => $emptyResource,
            'staff' => $emptyResource,
            'property_managers' => $emptyResource,
            'features' => [
                'company_profile' => false,
                'invoice_branding' => false,
                'roles_permissions' => false,
                'contact_login' => false,
            ],
        ];
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
