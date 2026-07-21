<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends BaseSaasController
{
    private const TARGET_ACCOUNT_TYPES = [
        'landlord',
        'estate_agent_freelance',
        'estate_agent_company',
    ];

    private const BOOLEAN_FIELDS = [
        'allow_company_profile',
        'allow_invoice_branding',
        'allow_roles_permissions',
        'allow_contact_login',
        'is_active',
    ];

    public function index()
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('backend.saas.plans.index', compact('plans'));
    }

    public function create()
    {
        $plan = new Plan([
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => 0,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $targetAccountTypes = self::TARGET_ACCOUNT_TYPES;

        return view('backend.saas.plans.create', compact('plan', 'targetAccountTypes'));
    }

    public function store(Request $request)
    {
        $this->normaliseCode($request);

        $data = $this->validatedData($request);
        $data['monthly_price_minor'] = $this->priceToMinor($data['monthly_price']);
        $data['annual_price_minor'] = $this->priceToMinor($data['annual_price']);
        unset($data['monthly_price'], $data['annual_price']);

        foreach (self::BOOLEAN_FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        Plan::create($data);

        flash('Plan created successfully.')->success();

        return redirect()->route('backend.saas.plans.index');
    }

    public function show(Plan $plan)
    {
        return redirect()->route('backend.saas.plans.edit', $plan);
    }

    public function edit(Plan $plan)
    {
        $targetAccountTypes = self::TARGET_ACCOUNT_TYPES;

        return view('backend.saas.plans.edit', compact('plan', 'targetAccountTypes'));
    }

    public function update(Request $request, Plan $plan)
    {
        $this->normaliseCode($request);

        $data = $this->validatedData($request, $plan);
        $data['monthly_price_minor'] = $this->priceToMinor($data['monthly_price']);
        $data['annual_price_minor'] = $this->priceToMinor($data['annual_price']);
        unset($data['monthly_price'], $data['annual_price']);

        foreach (self::BOOLEAN_FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        $plan->update($data);

        flash('Plan updated successfully.')->success();

        return redirect()->route('backend.saas.plans.index');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            flash('This plan is used by subscriptions. Deactivate it instead of deleting it.')->error();

            return back();
        }

        $plan->delete();

        flash('Plan deleted successfully.')->success();

        return redirect()->route('backend.saas.plans.index');
    }

    private function validatedData(Request $request, ?Plan $plan = null): array
    {
        $uniqueCode = Rule::unique('plans', 'code');
        if ($plan) {
            $uniqueCode = $uniqueCode->ignore($plan->id);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', $uniqueCode],
            'name' => ['required', 'string', 'max:255'],
            'target_account_type' => ['required', Rule::in(self::TARGET_ACCOUNT_TYPES)],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stripe_monthly_price_id' => ['nullable', 'string', 'max:255', 'regex:/^price_[A-Za-z0-9]+$/'],
            'stripe_annual_price_id' => ['nullable', 'string', 'max:255', 'regex:/^price_[A-Za-z0-9]+$/'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'property_limit' => ['required', 'integer', 'min:0'],
            'branch_limit' => ['required', 'integer', 'min:0'],
            'staff_limit' => ['required', 'integer', 'min:0'],
            'property_manager_limit' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer'],
        ]);
    }
}
