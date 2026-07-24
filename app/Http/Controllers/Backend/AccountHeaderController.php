<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AccountHeader;
use Illuminate\Http\Request;

class AccountHeaderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accountHeaders = AccountHeader::latest()->paginate(10);
        return view('backend.account_headers.index', compact('accountHeaders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.account_headers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'charge_on' => 'required',
            'who_can_view' => 'required',
            'transaction_between' => 'required'
        ]);

        AccountHeader::create([
            'name' => $request->name,
            'description' => $request->description,
            'charge_on' => $request->charge_on,
            'who_can_view' => $request->who_can_view,
            'reminders' => $request->reminders ? 1 : 0,
            'agent_fees' => $request->agent_fees ? 1 : 0,
            'require_bank_details' => $request->require_bank_details ? 1 : 0,
            'charge_in' => $request->charge_in,
            'can_have_duration' => $request->can_have_duration ? 1 : 0,
            'settle_through' => $request->settle_through,
            'duration_parameter_required' => $request->duration_parameter_required ? 1 : 0,
            'penalty_type' => $request->penalty_type,
            'tax_included' => $request->tax_included ? 1 : 0,
            'tax_type' => $request->tax_type,
            'transaction_between' => $request->transaction_between,
        ]);

        return redirect()->route('backend.account_headers.index')
                         ->with('success', 'Account Header created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccountHeader $accountHeader)
    {
        return view('backend.account_headers.edit', compact('accountHeader'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccountHeader $accountHeader)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'charge_on' => 'required',
            'who_can_view' => 'required',
            'transaction_between' => 'required'
        ]);

        $accountHeader->update([
            'name' => $request->name,
            'description' => $request->description,
            'charge_on' => $request->charge_on,
            'who_can_view' => $request->who_can_view,
            'reminders' => $request->reminders ? 1 : 0,
            'agent_fees' => $request->agent_fees ? 1 : 0,
            'require_bank_details' => $request->require_bank_details ? 1 : 0,
            'charge_in' => $request->charge_in,
            'can_have_duration' => $request->can_have_duration ? 1 : 0,
            'settle_through' => $request->settle_through,
            'duration_parameter_required' => $request->duration_parameter_required ? 1 : 0,
            'penalty_type' => $request->penalty_type,
            'tax_included' => $request->tax_included ? 1 : 0,
            'tax_type' => $request->tax_type,
            'transaction_between' => $request->transaction_between,
        ]);

        flash('Account Header updated successfully.')->success();
        return back();
        // return redirect()->route('backend.account_headers.index')
        //                  ->with('success', 'Account Header updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccountHeader $accountHeader)
    {
        $accountHeader->delete();
        return redirect()->route('backend.account_headers.index')
                         ->with('success', 'Account Header deleted successfully.');
    }
}
