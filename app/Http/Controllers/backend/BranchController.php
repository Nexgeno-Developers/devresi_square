<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Branch;

class BranchController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::all();
        return view('backend.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.branches.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'user_email' => 'nullable|email|max:255',
            'user_phone' => 'nullable|string|max:20',
        ]);

        Branch::create($request->all());
        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
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
        return view('backend.branches.edit', compact('branch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'user_email' => 'nullable|email|max:255',
            'user_phone' => 'nullable|string|max:20',
        ]);

        // Update the branch with the validated data
        $branch->update($validated);
        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        // Check if the branch exists
        if (!$branch) {
            return redirect()->route('admin.branches.index')->with('error', 'Branch not found.');
        }

        // Delete the branch
        $branch->delete();

        flash()->success('Branch deleted successfully.');
        return redirect()->route('admin.branches.index');
    }

}
