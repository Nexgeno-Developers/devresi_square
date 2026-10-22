<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RepairContractorRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\RepairJobVariation;
use App\Models\User;
use App\Services\Contractors\ContractorDirectory;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Services\Contractors\RepairJobOfferService;
use App\Services\Contractors\RepairMarketplacePanel;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RepairJobOfferController extends Controller
{
    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly RepairMarketplacePanel $panel,
        private readonly ContractorDirectory $directory,
        private readonly RepairJobAftercareService $aftercare,
    ) {
    }

    public function find(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);
        $desk = app(\App\Services\Repairs\RepairDeskService::class);
        if ($desk->openFor($repairIssue) || $desk->completedFor($repairIssue)) {
            return redirect()
                ->to(route('admin.property_repairs.show', $repairIssue).'#contractors')
                ->with('error', $desk->openFor($repairIssue)
                    ? 'The Resisquare team has this repair. A contractor can be invited only if they return it.'
                    : 'The Resisquare team has finished this repair.');
        }
        $filters = $request->only(['trade', 'distance', 'badges', 'only']);
        $result = $this->panel->find($repairIssue, (int) current_account_id(), $filters);
        if ($result['gap']) {
            $this->panel->recordGap($repairIssue, $result['trade']);
        }
        $contractorRequest = $repairIssue->contractorRequest;
        $urgency = $request->query('urgency', $contractorRequest?->urgency ?: ($result['emergency'] ? 'emergency' : 'routine'));

        return view('backend.repair.find', $result + [
            'repairIssue' => $repairIssue->loadMissing('property'),
            'filters' => $filters,
            'contractorRequest' => $contractorRequest,
            'urgency' => array_key_exists($urgency, RepairContractorRequest::URGENCIES) ? $urgency : 'routine',
            'openCount' => $repairIssue->jobOffers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->count(),
            'inviteHours' => collect(RepairContractorRequest::URGENCIES)->keys()->mapWithKeys(fn ($u) => [$u => ContractorSettings::inviteHours($u)])->all(),
        ]);
    }

    public function store(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);
        $data = $request->validate([
            'contractor_ids' => ['nullable', 'array'],
            'contractor_ids.*' => ['integer', 'exists:users,id'],
            'send_all' => ['nullable', 'boolean'],
            'urgency' => ['required', Rule::in(array_keys(RepairContractorRequest::URGENCIES))],
            'respond_by' => ['nullable', 'date', 'after:now'],
            'budget_ceiling' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'show_budget' => ['nullable', 'boolean'],
            'access_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'respond_by.after' => 'The respond-by time must be in the future.',
        ]);

        $ids = $data['contractor_ids'] ?? [];
        if ($data['urgency'] === 'emergency' && ! empty($data['send_all'])) {
            $ids = $this->panel->find($repairIssue, (int) current_account_id())['candidates']
                ->where('can_take', true)
                ->pluck('user_id')
                ->all();
        }
        if ($ids === []) {
            return back()->withInput()->with('error', 'Pick at least one contractor.');
        }

        try {
            $result = $this->offers->sendRequest($repairIssue, $ids, $request->user(), $data);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = $result['sent'] === [] ? 'Nothing was sent.' : 'Sent to '.implode(', ', $result['sent']).'.';
        if ($result['skipped'] !== []) {
            $message .= ' Not sent: '.implode(' ', $result['skipped']);
        }

        return redirect()
            ->route('admin.property_repairs.show', $repairIssue->id)
            ->with($result['sent'] === [] ? 'error' : 'success', $message);
    }

    public function invite(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        $result = $this->directory->invite((int) current_account_id(), $data['email'], $data['name'] ?? null, $request->user());

        return back()->with($result['status'], $result['message']);
    }

    public function favourite(Request $request, User $contractor)
    {
        abort_if(is_tenant_portal_user(), 403);
        abort_unless(WorkspaceAccess::canManageCurrentWorkspace($request->user()), 403);
        abort_unless($contractor->hasRole('Contractor'), 404);
        $on = $this->directory->toggleFavourite((int) current_account_id(), $contractor->id);

        return back()->with('success', $on ? 'Added to favourites.' : 'Removed from favourites.');
    }

    public function confirm(RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $offer->loadMissing('contractor.contractorProfile', 'repairIssue.property', 'request');
        if ($offer->status !== RepairJobOffer::OFFER_SENT) {
            return redirect()->route('admin.property_repairs.show', $offer->repair_issue_id)
                ->with('error', $offer->status === RepairJobOffer::OFFER_EXPIRED ? 'This offer has expired. Ask the contractor to reconfirm it.' : 'This offer is no longer open.');
        }

        return view('backend.repair.confirm-offer', [
            'offer' => $offer,
            'summary' => $this->offers->priceSummary($offer),
            'badges' => $offer->contractor?->contractorProfile ? ContractorCompliance::badges($offer->contractor->contractorProfile) : [],
            'others' => $offer->repairIssue->jobOffers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->where('id', '!=', $offer->id)->count(),
        ]);
    }

    public function accept(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:0']]);

        return $this->run(
            fn () => $this->offers->accept($offer, $request->user(), (int) $data['revision']),
            $offer->pricing_type === 'inspection'
                ? 'Inspection accepted. The contractor now has the address and will send a firm price after the visit.'
                : 'Job accepted. The contractor, tenant and other contractors have been told.',
            $offer
        );
    }

    public function decline(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);

        return $this->run(fn () => $this->offers->declineOffer($offer, $request->user()), 'Offer declined.', $offer);
    }

    public function reconfirm(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);

        return $this->run(fn () => $this->offers->requestReconfirm($offer, $request->user()), 'We asked the contractor to reconfirm. You can accept as soon as they do.', $offer);
    }

    public function withdraw(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);

        return $this->run(fn () => $this->offers->withdrawInvite($offer, $request->user()), 'Invitation withdrawn.', $offer);
    }

    public function cancel(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->run(fn () => $this->offers->cancel($offer, $request->user(), 'landlord', $data['reason']), 'Job cancelled. You can send the repair to contractors again.', $offer);
    }

    public function complete(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);

        return $this->run(fn () => $this->offers->confirmComplete($offer, $request->user()), 'Thanks. The job is confirmed complete.', $offer);
    }

    public function dispute(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return $this->run(fn () => $this->offers->dispute($offer, $request->user(), 'landlord', $data['reason']), 'Flagged. Our team will be in touch.', $offer);
    }

    public function close(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);
        $data = $request->validate([
            'outcome' => ['required', Rule::in([RepairContractorRequest::CLOSED, RepairContractorRequest::CANCELLED])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        try {
            $this->offers->closeRequest($repairIssue, $request->user(), $data['outcome'], $data['reason'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $data['outcome'] === RepairContractorRequest::CANCELLED
            ? 'Request cancelled. Contractors have been told.'
            : 'All offers rejected. You can send the repair to other contractors.');
    }

    public function message(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->run(fn () => $this->aftercare->postMessage($offer, $request->user(), RepairJobMessage::LANDLORD, $data['body']), 'Reply sent.', $offer);
    }

    public function decideVariation(Request $request, RepairJobOffer $offer, RepairJobVariation $variation)
    {
        $this->authorizeRepair($offer->repairIssue);
        abort_unless((int) $variation->repair_job_offer_id === (int) $offer->id, 404);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'decline'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->run(
            fn () => $this->aftercare->decideVariation($variation, $request->user(), $data['decision'] === 'approve', $data['note'] ?? null),
            $data['decision'] === 'approve' ? 'Extra work approved. The contractor has been told.' : 'Extra work declined.',
            $offer
        );
    }

    public function reschedule(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1', 'max:3'],
            'slots.*' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->run(fn () => $this->aftercare->requestReschedule($offer, $request->user(), 'landlord', $data['slots'], $data['note'] ?? null), 'New times sent to the contractor.', $offer);
    }

    public function rate(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'rating_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->run(fn () => $this->aftercare->rate($offer, $request->user(), (int) $data['rating'], $data['rating_comment'] ?? null), 'Thanks for rating the contractor.', $offer);
    }

    public function completionPhoto(RepairJobOffer $offer, int $index)
    {
        $this->authorizeRepair($offer->repairIssue);
        $path = ($offer->completion_photos ?? [])[$index] ?? null;
        abort_unless($path && Storage::disk(RepairJobAftercareService::PHOTO_DISK)->exists($path), 404);

        return Storage::disk(RepairJobAftercareService::PHOTO_DISK)->response($path);
    }

    public function invoice(RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $offer->load(['invoice', 'repairIssue.property', 'contractor.contractorProfile']);
        abort_unless($offer->invoice, 404);

        return view('backend.repair.job-invoice', [
            'offer' => $offer,
            'invoice' => $offer->invoice,
            'profile' => $offer->contractor?->contractorProfile,
        ]);
    }

    public function invoicePaid(Request $request, RepairJobOffer $offer)
    {
        $this->authorizeRepair($offer->repairIssue);
        $invoice = $offer->invoice;
        abort_unless($invoice, 404);
        try {
            $this->aftercare->markPaid($invoice, $request->user());
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.property_repairs.job_offers.invoice', $offer)->with('success', 'Marked as paid. The contractor has been told.');
    }

    private function run(callable $action, string $success, RepairJobOffer $offer)
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return redirect()->to(route('admin.property_repairs.show', $offer->repair_issue_id).'#contractors')->with('error', $exception->getMessage());
        }

        return redirect()->to(route('admin.property_repairs.show', $offer->repair_issue_id).'#contractors')->with('success', $success);
    }

    private function authorizeRepair(?RepairIssue $repair): void
    {
        abort_unless($repair, 404);
        abort_if(is_tenant_portal_user(), 403);
        ensureModelBelongsToCurrentAccount($repair);
        Gate::authorize('update', $repair);
    }
}
