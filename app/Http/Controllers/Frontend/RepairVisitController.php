<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\RepairJobOffer;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Services\Contractors\RepairJobOfferService;
use Illuminate\Http\Request;

/** Signed link the tenant gets by email: confirm the visit time or offer new ones. */
class RepairVisitController extends Controller
{
    public function show(RepairJobOffer $offer)
    {
        $offer->loadMissing('repairIssue');

        return view('frontend.repair-visit-confirm', [
            'offer' => $offer,
            'when' => $offer->scheduled_for ?: $offer->earliestSlot(),
            'active' => in_array($offer->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true) && ! $offer->isFinished(),
        ]);
    }

    public function confirm(Request $request, RepairJobOffer $offer, RepairJobOfferService $offers, RepairJobAftercareService $aftercare)
    {
        try {
            if ($request->input('action') === 'reschedule') {
                $data = $request->validate([
                    'slots' => ['required', 'array', 'min:1', 'max:3'],
                    'slots.*' => ['nullable', 'date'],
                    'note' => ['nullable', 'string', 'max:500'],
                ], ['slots.required' => 'Pick at least one time that works.']);
                $aftercare->requestReschedule($offer, $offer->repairIssue?->tenant, 'tenant', $data['slots'], $data['note'] ?? null);

                return back()->with('success', 'Thanks. The contractor will pick one of your times and we’ll confirm it.');
            }
            $offers->tenantConfirm($offer);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Thanks, the contractor knows the time works for you.');
    }
}
