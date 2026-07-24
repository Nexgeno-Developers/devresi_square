<?php

namespace App\Http\Controllers\Backend;

use App\Models\TenancySubStatus;
use Illuminate\Http\Request;

class TenancySubStatusController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all TenancySubStatus records
        $tenancySubStatuses = TenancySubStatus::all();
        return view('backend.tenancy_sub_statuses.index', compact('tenancySubStatuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.tenancy_sub_statuses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|unique:tenancy_sub_statuses|max:255',
        ]);

        // Store the new TenancySubStatus in the database
        TenancySubStatus::create($request->all());
        flash("Added successfully!")->success();
        // Redirect back to the index page
        return redirect()->route('admin.tenancy_sub_statuses.index')->with('success', 'Tenancy Sub Status created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TenancySubStatus $tenancySubStatus)
    {
        // Display the specific TenancySubStatus details
        return view('backend.tenancy_sub_statuses.show', compact('tenancySubStatus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TenancySubStatus $tenancySubStatus)
    {
        // Show the form to edit the specific TenancySubStatus
        return view('backend.tenancy_sub_statuses.edit', compact('tenancySubStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TenancySubStatus $tenancySubStatus)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|unique:tenancy_sub_statuses,name,' . $tenancySubStatus->id . '|max:255',
        ]);

        // Update the TenancySubStatus record
        $tenancySubStatus->update($request->all());

        // Redirect back to the index page
        return redirect()->route('admin.tenancy_sub_statuses.index')->with('success', 'Tenancy Sub Status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TenancySubStatus $tenancySubStatus)
    {
        // Delete the TenancySubStatus record
        $tenancySubStatus->delete();

        // Redirect back to the index page
        return redirect()->route('admin.tenancy_sub_statuses.index')->with('success', 'Tenancy Sub Status deleted successfully.');
    }
}
