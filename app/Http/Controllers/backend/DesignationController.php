<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Designation;
use Spatie\Permission\Models\Permission;

class DesignationController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $designations = Designation::withCount('permissions')->get();
        return view('backend.designations.index', compact('designations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('backend.designations.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:designations',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $designation = Designation::create($request->only('title'));
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
        $designation = Designation::with('permissions')->findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        $selectedPermissions = $designation->permissions->pluck('id')->toArray();

        return view('backend.designations.edit', compact('designation', 'permissions', 'selectedPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:designations,title,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $designation = Designation::findOrFail($id);
        $designation->update($request->only('title'));
        $designation->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.designations.index')->with('success', 'Designation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();

        return redirect()->route('admin.designations.index')->with('success', 'Designation deleted successfully.');
    }
}
