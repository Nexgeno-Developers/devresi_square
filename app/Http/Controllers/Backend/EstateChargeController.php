<?php

namespace App\Http\Controllers\Backend;

use App\Models\EstateCharge;
use Illuminate\Http\Request;

class EstateChargeController
{
    /**
     * Display a listing of the estate charges.
     */
    public function index()
    {
        $estateCharges = EstateCharge::all();
        return view('backend.estate_charges.index', compact('estateCharges'));
    }

    /**
     * Show the form for creating a new estate charge.
     */
    public function create()
    {
        return view('backend.estate_charges.create');
    }

    /**
     * Store a newly created estate charge in the database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ref_no' => 'required|integer',
            'property_id' => 'required|exists:properties,id',
            'description' => 'required|string|max:550',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'status' => 'required|string',
            // Add other fields as needed
        ]);

        EstateCharge::create($validatedData);

        return redirect()->route('backend.estate_charges.index')
            ->with('success', 'Estate charge created successfully.');
    }

    /**
     * Show the form for editing the specified estate charge.
     */
    public function edit(EstateCharge $estateCharge)
    {
        return view('backend.estate_charges.edit', compact('estateCharge'));
    }

    /**
     * Update the specified estate charge in the database.
     */
    public function update(Request $request, EstateCharge $estateCharge)
    {
        $validatedData = $request->validate([
            'ref_no' => 'required|integer',
            'property_id' => 'required|exists:properties,id',
            'description' => 'required|string|max:550',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'status' => 'required|string',
            // Add other fields as needed
        ]);

        $estateCharge->update($validatedData);

        return redirect()->route('backend.estate_charges.index')
            ->with('success', 'Estate charge updated successfully.');
    }

    /**
     * Remove the specified estate charge from the database.
     */
    public function destroy(EstateCharge $estateCharge)
    {
        $estateCharge->delete();

        return redirect()->route('backend.estate_charges.index')
            ->with('success', 'Estate charge deleted successfully.');
    }
}
