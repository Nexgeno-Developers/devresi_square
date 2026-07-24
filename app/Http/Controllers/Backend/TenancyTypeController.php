<?php

namespace App\Http\Controllers\Backend;

use App\Models\TenancyType;
use Illuminate\Http\Request;

class TenancyTypeController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenancyTypes = TenancyType::all();
        return view('backend.tenancy_types.index', compact('tenancyTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.tenancy_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:tenancy_types',
        ]);

        TenancyType::create($request->all());
        flash("Added successfully!")->success();
        return redirect()->route('admin.tenancy_types.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(TenancyType $tenancyType)
    {
        // Passing the tenancy type data to the view
        return view('backend.tenancy_types.show', compact('tenancyType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TenancyType $tenancyType)
    {
        return view('backend.tenancy_types.edit', compact('tenancyType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TenancyType $tenancyType)
    {
        $request->validate([
            'name' => 'required|unique:tenancy_types,type,' . $tenancyType->id,
        ]);

        $tenancyType->update($request->all());
        flash("Updated successfully!")->success();
        return redirect()->route('admin.tenancy_types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TenancyType $tenancyType)
    {
        $tenancyType->delete();

        return redirect()->route('admin.tenancy_types.index');
    }
}
