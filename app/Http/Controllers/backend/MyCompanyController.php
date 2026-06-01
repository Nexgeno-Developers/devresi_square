<?php

namespace App\Http\Controllers\Backend;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MyCompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view own company')->only('show');
        $this->middleware('permission:edit own company')->only(['edit', 'update']);
    }

    public function show()
    {
        $company = $this->resolveCompany();

        if (!$company) {
            return view('backend.company.show', ['company' => null]);
        }

        return view('backend.company.show', compact('company'));
    }

    public function edit()
    {
        $company = $this->resolveCompany();

        if (!$company) {
            flash('No company profile found.')->warning();
            return redirect()->route('backend.dashboard');
        }

        return view('backend.company.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $company = $this->resolveCompany();

        if (!$company) {
            flash('No company profile found.')->error();
            return back();
        }

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'registration_number'    => 'nullable|string|max:100',
            'registered_address'     => 'nullable|string|max:500',
            'communication_address'  => 'nullable|string|max:500',
            'vat_number'             => 'nullable|string|max:50',
            'website'                => 'nullable|url|max:255',
            'logo'                   => 'nullable|image|max:2048',
            'emails'                 => 'nullable|array',
            'emails.*'               => 'nullable|email',
            'phones'                 => 'nullable|array',
            'phones.*'               => 'nullable|string|max:20',
            'cmp_scheme'             => 'nullable|string|max:100',
            'cmp_expiry_date'        => 'nullable|date',
            'redress_scheme'         => 'nullable|string|max:100',
            'redress_membership_number' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        $validated['emails'] = array_filter($validated['emails'] ?? []);
        $validated['phones'] = array_filter($validated['phones'] ?? []);

        $company->update([
            'name'                      => $validated['name'],
            'registration_number'       => $validated['registration_number'] ?? null,
            'registered_address'        => $validated['registered_address'] ?? null,
            'communication_address'     => $validated['communication_address'] ?? null,
            'vat_number'                => $validated['vat_number'] ?? null,
            'website'                   => $validated['website'] ?? null,
            'logo_path'                 => $validated['logo_path'] ?? $company->logo_path,
            'emails'                    => array_values($validated['emails']),
            'phones'                    => array_values($validated['phones']),
            'cmp_scheme'                => $validated['cmp_scheme'] ?? null,
            'cmp_expiry_date'           => $validated['cmp_expiry_date'] ?? null,
            'redress_scheme'            => $validated['redress_scheme'] ?? null,
            'redress_membership_number' => $validated['redress_membership_number'] ?? null,
            'updated_by'                => Auth::id(),
        ]);

        flash('Company profile updated successfully.')->success();
        return redirect()->route('my_company.show');
    }

    private function resolveCompany(): ?Company
    {
        $user = Auth::user();
        return $user->ownedCompany ?? $user->company ?? null;
    }
}
