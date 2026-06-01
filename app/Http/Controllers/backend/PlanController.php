<?php

namespace App\Http\Controllers\Backend;

use App\Models\Plan;
use App\Models\UserPlan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function __construct()
    {
        // CRUD — Super Admin only
        $this->middleware('permission:manage plans')->only([
            'index', 'create', 'store', 'edit', 'update', 'destroy', 'toggleActive',
        ]);
        // myPlan — any authenticated user
    }

    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('id')->get();
        return view('backend.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('backend.plans.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'price_monthly'  => 'required|numeric|min:0',
            'price_yearly'   => 'required|numeric|min:0',
            'max_properties' => 'nullable|integer|min:1',
            'max_staff'      => 'nullable|integer|min:1',
            'max_tenancies'  => 'nullable|integer|min:1',
            'features'       => 'nullable|array',
            'features.*'     => 'nullable|string|max:200',
            'badge_label'    => 'nullable|string|max:50',
            'is_featured'    => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $data['slug']        = Str::slug($data['name']);
        $data['features']    = $this->cleanFeatures($request->input('features', []));
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active']   = $request->boolean('is_active', true);
        $data['sort_order']  = $data['sort_order'] ?? 0;

        Plan::create($data);

        flash('Plan created successfully.')->success();
        return redirect()->route('admin.plans.index');
    }

    public function edit(Plan $plan)
    {
        return view('backend.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:500',
            'price_monthly'  => 'required|numeric|min:0',
            'price_yearly'   => 'required|numeric|min:0',
            'max_properties' => 'nullable|integer|min:1',
            'max_staff'      => 'nullable|integer|min:1',
            'max_tenancies'  => 'nullable|integer|min:1',
            'features'       => 'nullable|array',
            'features.*'     => 'nullable|string|max:200',
            'badge_label'    => 'nullable|string|max:50',
            'is_featured'    => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $data['features']    = $this->cleanFeatures($request->input('features', []));
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active']   = $request->boolean('is_active', true);
        $data['sort_order']  = $data['sort_order'] ?? $plan->sort_order;

        $plan->update($data);

        flash('Plan updated successfully.')->success();
        return redirect()->route('admin.plans.index');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->activeSubscriptionsCount() > 0) {
            flash('Cannot delete a plan with active subscribers.')->error();
            return back();
        }
        $plan->delete();
        flash('Plan deleted.')->success();
        return redirect()->route('admin.plans.index');
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        flash('Plan ' . ($plan->fresh()->is_active ? 'activated' : 'deactivated') . '.')->success();
        return back();
    }

    public function myPlan()
    {
        $user     = Auth::user();
        $company  = $user->ownedCompany ?? $user->company ?? null;
        $userPlan = $company
            ? UserPlan::where('company_id', $company->id)
                ->where('status', 'active')
                ->with('plan')
                ->latest()
                ->first()
            : null;

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('backend.plans.my_plan', compact('userPlan', 'plans', 'company'));
    }

    private function cleanFeatures(array $raw): array
    {
        return array_values(array_filter(array_map('trim', $raw)));
    }
}
