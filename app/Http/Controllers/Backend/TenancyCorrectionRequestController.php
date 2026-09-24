<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Tenancy;
use App\Models\TenancyCorrectionRequest;
use App\Services\Portal\TenancyDetailsConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TenancyCorrectionRequestController extends Controller
{
    public function approve(
        Request $request,
        Tenancy $tenancy,
        TenancyCorrectionRequest $correction,
        TenancyDetailsConfirmationService $confirmation,
    ): RedirectResponse {
        $this->assertRequest($tenancy, $correction);
        Gate::authorize('update', $tenancy);

        $editableKeys = collect($correction->fields ?? [])
            ->pluck('key')
            ->filter(fn ($key) => TenancyDetailsConfirmationService::FIELDS[$key]['editable'] ?? false)
            ->values()
            ->all();

        $validated = $request->validate([
            'move_in' => [Rule::requiredIf(in_array('move_in', $editableKeys, true)), 'nullable', 'date'],
            'move_out' => ['nullable', 'date', 'after_or_equal:move_in'],
            'rent' => [Rule::requiredIf(in_array('rent', $editableKeys, true)), 'nullable', 'numeric', 'min:0'],
            'frequency' => ['nullable', Rule::in(['Monthly', 'Weekly'])],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'term_months' => ['nullable', 'integer', 'min:0', 'max:60'],
            'landlord_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $confirmation->approve(
            $request->user(),
            $correction,
            $validated,
            $validated['landlord_note'] ?? null,
        );

        flash('Tenant correction applied. They will be asked to confirm the updated details.')->success();

        return back();
    }

    public function reject(
        Request $request,
        Tenancy $tenancy,
        TenancyCorrectionRequest $correction,
        TenancyDetailsConfirmationService $confirmation,
    ): RedirectResponse {
        $this->assertRequest($tenancy, $correction);
        Gate::authorize('update', $tenancy);

        $validated = $request->validate([
            'landlord_note' => ['required', 'string', 'max:2000'],
        ]);

        $confirmation->reject($request->user(), $correction, $validated['landlord_note']);

        flash('Correction request declined. The tenant will be asked to confirm the original details.')->success();

        return back();
    }

    private function assertRequest(Tenancy $tenancy, TenancyCorrectionRequest $correction): void
    {
        abort_unless((int) $tenancy->account_id === (int) current_account_id(), 404);
        abort_unless((int) $correction->tenancy_id === (int) $tenancy->id, 404);
        abort_unless((int) $correction->account_id === (int) current_account_id(), 404);
    }
}
