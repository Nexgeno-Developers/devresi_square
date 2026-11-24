<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\AccountSubscription;
use App\Models\Plan;
use App\Services\Saas\TrialExtensionService;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class SubscriptionController extends BaseSaasController
{
    private const STATUSES = [
        'trialing',
        'active',
        'past_due',
        'cancelled',
        'expired',
    ];

    private const BILLING_CYCLES = [
        'monthly',
        'annual',
    ];

    private const ACCOUNT_TYPES = [
        'landlord',
        'estate_agent_freelance',
        'estate_agent_company',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'billing_cycle', 'plan_id', 'account_type', 'search']);

        $subscriptions = AccountSubscription::query()
            ->with(['account.owner', 'plan'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('billing_cycle'), fn ($query) => $query->where('billing_cycle', $request->billing_cycle))
            ->when($request->filled('plan_id'), fn ($query) => $query->where('plan_id', $request->plan_id))
            ->when($request->filled('account_type'), function ($query) use ($request) {
                $query->whereHas('account', fn ($accountQuery) => $accountQuery->where('account_type', $request->account_type));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim($request->search) . '%';

                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('stripe_subscription_id', 'like', $search)
                        ->orWhereHas('account', fn ($accountQuery) => $accountQuery->where('account_name', 'like', $search));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $plans = Plan::query()->orderBy('name')->get();
        $statuses = self::STATUSES;
        $billingCycles = self::BILLING_CYCLES;
        $accountTypes = self::ACCOUNT_TYPES;

        return view('backend.saas.subscriptions.index', compact(
            'subscriptions',
            'plans',
            'statuses',
            'billingCycles',
            'accountTypes',
            'filters'
        ));
    }

    public function show(AccountSubscription $subscription)
    {
        $subscription->load([
            'account.owner',
            'plan',
            'accountSubscriptionAddons.addon',
        ]);

        return view('backend.saas.subscriptions.show', compact('subscription'));
    }

    public function extendTrial(Request $request, AccountSubscription $subscription, TrialExtensionService $service)
    {
        $data = $request->validate([
            'additional_days' => ['required', 'integer', 'min:1', 'max:730'],
        ]);

        $before = $subscription->only(['trial_ends_at']);
        try {
            $service->extend($subscription, (int) $data['additional_days']);
            // A trial extension is free access, so keep a record of who gave it and how long.
            AuditTrail::record($subscription->account_id, $request->user()->id, 'subscription_trial_extended', $subscription, [
                'trial_ends_at' => $before['trial_ends_at']?->toDateTimeString(),
            ], [
                'trial_ends_at' => $subscription->trial_ends_at?->toDateTimeString(),
                'additional_days' => (int) $data['additional_days'],
                'in_stripe' => (bool) $subscription->stripe_subscription_id,
            ]);
            flash('Trial extended to '.$subscription->trial_ends_at->format('j M Y').($subscription->stripe_subscription_id ? ' in Stripe and the application.' : '. This trial has no Stripe subscription yet, so only the application was changed.'))->success();
        } catch (\Throwable $exception) {
            flash('Trial extension failed: ' . $exception->getMessage())->error();
        }

        return redirect()->route('backend.saas.subscriptions.show', $subscription);
    }
}
