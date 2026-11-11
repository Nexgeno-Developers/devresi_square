<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ActsAsMessagingParty;
use App\Models\ComplianceJobMessage;
use App\Models\ComplianceJobOffer;
use App\Models\ContractorProfile;
use App\Models\MessageRequest;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Services\Contractors\ComplianceJobAftercareService;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Services\Messaging\InboxFeed;
use App\Support\Messaging\MessagingArea;
use Illuminate\Http\Request;

/**
 * Repair and compliance job chats shown inside the inbox. Reading and
 * posting go through the existing job services, so masking before award
 * and "closed job" rules stay exactly as on the job pages.
 */
class JobThreadController extends Controller
{
    use ActsAsMessagingParty;

    public function __construct(
        private readonly RepairJobAftercareService $repairAftercare,
        private readonly ComplianceJobAftercareService $complianceAftercare,
        private readonly InboxFeed $feed,
    ) {}

    public function repair(Request $request, RepairJobOffer $repairOffer)
    {
        $side = $this->authorizeRepair($request, $repairOffer);
        $repairOffer->load(['messages.user', 'repairIssue.property', 'contractor.contractorProfile']);
        $this->repairAftercare->markRead($repairOffer, $side);
        $isAdmin = $this->area($request) === MessagingArea::ADMIN;

        return view('messaging.job', $this->shared($request) + [
            'kind' => 'repair',
            'offer' => $repairOffer,
            'title' => $isAdmin
                ? ($repairOffer->contractor?->contractorProfile?->displayName() ?: ($repairOffer->contractor?->name ?: 'Contractor'))
                : (\App\Models\Account::query()->whereKey($repairOffer->account_id)->value('account_name') ?: 'Landlord'),
            'label' => 'Job · '.($repairOffer->repairIssue?->reference_number ?: '#'.$repairOffer->repair_issue_id),
            'messages' => $repairOffer->messages->sortBy('id')->values(),
            'mySide' => $side,
            'canMessage' => $repairOffer->canMessage(),
            'sendUrl' => $this->routeTo($request, 'jobs.repair.send', $repairOffer),
            'jobUrl' => $isAdmin ? route('admin.property_repairs.show', $repairOffer->repair_issue_id) : route('contractor.jobs.show', $repairOffer),
        ]);
    }

    public function repairSend(Request $request, RepairJobOffer $repairOffer)
    {
        $side = $this->authorizeRepair($request, $repairOffer);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->run($request, fn () => $this->repairAftercare->postMessage($repairOffer, $request->user(), $side, $data['body']),
            $this->routeTo($request, 'jobs.repair', $repairOffer));
    }

    public function compliance(Request $request, ComplianceJobOffer $complianceOffer)
    {
        $side = $this->authorizeCompliance($request, $complianceOffer);
        $complianceOffer->load(['messages.user', 'booking.type', 'booking.property', 'contractor.contractorProfile']);
        if ($complianceOffer->canMessage()) {
            $this->complianceAftercare->markRead($complianceOffer, $side);
        }
        $isAdmin = $this->area($request) === MessagingArea::ADMIN;

        return view('messaging.job', $this->shared($request) + [
            'kind' => 'compliance',
            'offer' => $complianceOffer,
            'title' => $isAdmin
                ? $complianceOffer->contractorName()
                : (\App\Models\Account::query()->whereKey($complianceOffer->account_id)->value('account_name') ?: 'Landlord'),
            'label' => 'Check · '.($complianceOffer->booking?->type?->name ?? 'Compliance'),
            'messages' => $complianceOffer->messages->sortBy('id')->values(),
            'mySide' => $side,
            'canMessage' => $complianceOffer->canMessage(),
            'sendUrl' => $this->routeTo($request, 'jobs.compliance.send', $complianceOffer),
            'jobUrl' => $isAdmin
                ? route('admin.compliance.bookings.show', $complianceOffer->compliance_booking_id)
                : route('contractor.jobs.compliance.show', $complianceOffer),
        ]);
    }

    public function complianceSend(Request $request, ComplianceJobOffer $complianceOffer)
    {
        $side = $this->authorizeCompliance($request, $complianceOffer);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->run($request, fn () => $this->complianceAftercare->postMessage($complianceOffer, $request->user(), $side, $data['body']),
            $this->routeTo($request, 'jobs.compliance', $complianceOffer));
    }

    /** Same checks as the job pages: the landlord's account, or the invited contractor. */
    private function authorizeRepair(Request $request, RepairJobOffer $offer): string
    {
        $party = $this->party($request);
        if ($this->area($request) === MessagingArea::ADMIN) {
            abort_unless((int) $offer->account_id === $party->id && (int) current_account_id() === $party->id, 404);

            return RepairJobMessage::LANDLORD;
        }
        abort_unless((int) $offer->contractor_user_id === $party->id, 404);
        $this->assertApprovedContractor($request);

        return RepairJobMessage::CONTRACTOR;
    }

    private function authorizeCompliance(Request $request, ComplianceJobOffer $offer): string
    {
        $party = $this->party($request);
        if ($this->area($request) === MessagingArea::ADMIN) {
            abort_unless((int) $offer->account_id === $party->id && (int) current_account_id() === $party->id, 404);

            return ComplianceJobMessage::LANDLORD;
        }
        abort_unless((int) $offer->contractor_user_id === $party->id, 404);
        $this->assertApprovedContractor($request);

        return ComplianceJobMessage::CONTRACTOR;
    }

    private function assertApprovedContractor(Request $request): void
    {
        $profile = $request->user()->contractorProfile;
        abort_unless($profile && $profile->approval_status === ContractorProfile::APPROVED, 403, 'Your contractor profile needs approval first.');
    }

    private function run(Request $request, callable $action, string $back)
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return redirect($back)->withInput()->with('messaging_error', $exception->getMessage());
        }

        return redirect($back.'#latest')->with('messaging_status', 'Sent.');
    }

    private function shared(Request $request): array
    {
        $party = $this->party($request);

        return MessagingArea::layoutFlags($this->area($request)) + [
            'party' => $party,
            'pendingRequests' => MessageRequest::query()->where('to_type', $party->type)->where('to_id', $party->id)->where('status', MessageRequest::PENDING)->count(),
            'sidebarItems' => $this->feed->items($party, $request->user(), $this->area($request)),
        ];
    }
}
