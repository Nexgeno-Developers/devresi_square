<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class BranchController
{
    use EnforcesSaasPlanLimits;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $company = $this->branchCompanyFor(auth()->user(), false);
        $branchesQuery = Branch::query();

        if (! auth()->user()?->hasRole('Super Admin')) {
            $branchesQuery->forAccount(current_account_id());
        }

        $branches = $company
            ? $branchesQuery->where('company_id', $company->id)->get()
            : (auth()->user()?->hasRole('Super Admin') ? $branchesQuery->get() : collect());

        return view('backend.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if ($response = $this->redirectIfSaasLimitDenied('branch', 'admin.branches.index')) {
            return $response;
        }

        $company = $this->branchCompanyFor(auth()->user());

        return view('backend.branches.create', compact('company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($response = $this->backIfSaasLimitDenied('branch')) {
            return $response;
        }

        $validated = $request->validate($this->validationRules());

        $company = $this->branchCompanyFor(auth()->user());
        $isMainHeadOffice = $request->boolean('is_main_head_office');

        DB::transaction(function () use ($validated, $company, $isMainHeadOffice) {
            if ($isMainHeadOffice) {
                Branch::where('company_id', $company->id)->update(['is_main_head_office' => false]);
            }

            Branch::create(array_merge($validated, [
                'account_id' => current_account_id(),
                'company_id' => $company->id,
                'is_main_head_office' => $isMainHeadOffice,
                'created_by' => auth()->id(),
            ]));
        });

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }

    private function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'user_phone' => 'nullable|string|max:20',
            'alternate_email' => 'nullable|email|max:255',
            'alternate_phone' => 'nullable|string|max:20',
            'social_media' => 'nullable|array',
            'social_media.facebook' => 'nullable|url|max:255',
            'social_media.instagram' => 'nullable|url|max:255',
            'social_media.linkedin' => 'nullable|url|max:255',
            'social_media.twitter' => 'nullable|url|max:255',
            'social_media.youtube' => 'nullable|url|max:255',
            'social_media.tiktok' => 'nullable|url|max:255',
            'social_media.website' => 'nullable|url|max:255',
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        ensureModelBelongsToCurrentAccount($branch);

        $company = $this->branchCompanyFor(auth()->user(), false);
        if ($company && (int) $branch->company_id !== (int) $company->id) {
            abort(403, 'Unauthorized to edit this branch.');
        }

        return view('backend.branches.edit', compact('branch', 'company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        ensureModelBelongsToCurrentAccount($branch);

        $validated = $request->validate($this->validationRules());

        $company = $this->branchCompanyFor(auth()->user(), false);
        if ($company && (int) $branch->company_id !== (int) $company->id) {
            abort(403, 'Unauthorized to update this branch.');
        }

        $companyId = $company?->id ?: $branch->company_id;
        $isMainHeadOffice = $request->boolean('is_main_head_office');

        DB::transaction(function () use ($validated, $branch, $companyId, $isMainHeadOffice) {
            if ($isMainHeadOffice) {
                Branch::where('company_id', $companyId)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_main_head_office' => false]);
            }

            $branch->update(array_merge($validated, [
                'account_id' => $branch->account_id ?: current_account_id(),
                'company_id' => $companyId,
                'is_main_head_office' => $isMainHeadOffice,
            ]));
        });

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        ensureModelBelongsToCurrentAccount($branch);

        // Check if the branch exists
        if (!$branch) {
            return redirect()->route('admin.branches.index')->with('error', 'Branch not found.');
        }

        // Delete the branch
        $company = $this->branchCompanyFor(auth()->user(), false);
        if ($company && (int) $branch->company_id !== (int) $company->id) {
            abort(403, 'Unauthorized to delete this branch.');
        }

        $branch->delete();

        flash()->success('Branch deleted successfully.');
        return redirect()->route('admin.branches.index');
    }

    private function branchCompanyFor($user, bool $createIfMissing = true): ?Company
    {
        if (! $user) {
            return null;
        }

        if (current_account()?->company) {
            return current_account()->company;
        }

        if ($user->ownedCompany) {
            return $user->ownedCompany;
        }

        if ($user->company_id) {
            return Company::find($user->company_id);
        }

        if (! $createIfMissing) {
            return null;
        }

        $this->abortIfSaasLimitDenied('company_profile');

        return $user->ownedCompany()->firstOrCreate(
            ['owner_user_id' => $user->id],
            [
                'account_id' => current_account_id(),
                'name' => $user->name ? $user->name . ' Company' : 'My Company',
                'created_by' => $user->id,
            ]
        );
    }

}
