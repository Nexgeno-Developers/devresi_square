<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\EnforcesSaasPlanLimits;
use Illuminate\Http\Request;
use App\Models\Designation;
use Spatie\Permission\Models\Permission;

class DesignationController
{
    use EnforcesSaasPlanLimits;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $designations = Designation::withCount('permissions')
            ->when(! auth()->user()?->hasRole('Super Admin'), fn ($query) => $query->forAccount(current_account_id()))
            ->get();

        return view('backend.designations.index', compact('designations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $permissions = Permission::orderBy('name')->get();
        return view('backend.designations.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $request->validate([
            'title' => 'required|string|max:255|unique:designations',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $designation = Designation::create([
            'account_id' => current_account_id(),
            'title' => $request->input('title'),
            'status' => 'active',
        ]);
        $designation->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.designations.index')->with('success', 'Designation created successfully.');
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
    public function edit($id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $designation = Designation::with('permissions')->findOrFail($id);
        ensureModelBelongsToCurrentAccount($designation);

        $permissions = Permission::orderBy('name')->get();
        $selectedPermissions = $designation->permissions->pluck('id')->toArray();

        return view('backend.designations.edit', compact('designation', 'permissions', 'selectedPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $request->validate([
            'title' => 'required|string|max:255|unique:designations,title,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $designation = Designation::findOrFail($id);
        ensureModelBelongsToCurrentAccount($designation);

        $designation->update($request->only('title'));
        $designation->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.designations.index')->with('success', 'Designation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->abortIfSaasLimitDenied('roles_permissions');

        $designation = Designation::findOrFail($id);
        ensureModelBelongsToCurrentAccount($designation);

        $designation->delete();

        return redirect()->route('admin.designations.index')->with('success', 'Designation deleted successfully.');
    }
}
