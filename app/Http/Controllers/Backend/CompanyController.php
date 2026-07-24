<?php

namespace App\Http\Controllers\Backend;

use App\Models\Company;
use App\Models\CompanyOwnerTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function transferOwner(Request $request, Company $company)
    {
        $authUser = auth()->user();

        if (! $authUser || (! $authUser->can('transfer company owner') && (int) $company->owner_user_id !== (int) $authUser->id)) {
            abort(403, 'Unauthorized to transfer this company.');
        }

        $validated = $request->validate([
            'new_owner_user_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $newOwner = User::findOrFail($validated['new_owner_user_id']);
        if (
            ! in_array($newOwner->user_type, ['agent', 'estate_agent'], true)
            && ! $newOwner->hasAnyRole(['Agent', 'Estate Agent'])
        ) {
            return back()->withErrors(['new_owner_user_id' => 'The new owner must be an Agent or Estate Agent account.'])->withInput();
        }

        DB::transaction(function () use ($company, $newOwner, $authUser, $validated) {
            $oldOwnerId = $company->owner_user_id;

            $company->update([
                'owner_user_id' => $newOwner->id,
                'updated_by' => $authUser->id,
            ]);

            CompanyOwnerTransfer::create([
                'company_id' => $company->id,
                'old_owner_user_id' => $oldOwnerId,
                'new_owner_user_id' => $newOwner->id,
                'transferred_by' => $authUser->id,
                'note' => $validated['note'] ?? null,
                'transferred_at' => now(),
            ]);
        });

        flash('Company owner transferred successfully.')->success();

        return back();
    }
}
