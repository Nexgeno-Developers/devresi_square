<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ComplianceBooking;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceType;
use App\Models\Property;
use App\Services\Contractors\ComplianceBookingService;
use App\Services\Contractors\ContractorDirectory;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Support\ContractorCompliance;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class ComplianceBookingController extends Controller
{
    public function __construct(
        private readonly ComplianceBookingService $bookings,
        private readonly ContractorDirectory $directory,
    ) {
    }

    public function store(Request $request)
    {
        $accountId = $this->accountId();
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'compliance_type_id' => ['required', 'integer'],
        ]);
        $property = Property::query()->forAccount($accountId)->findOrFail($data['property_id']);
        $type = ComplianceType::query()->whereIn('alias', array_keys(ComplianceBooking::TRADES))->findOrFail($data['compliance_type_id']);

        try {
            $booking = $this->bookings->open($accountId, $property, $type);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->to($booking->landlordUrl());
    }

    public function find(Request $request, ComplianceBooking $booking)
    {
        $this->authorizeBooking($booking);
        if ($booking->status === ComplianceBooking::BOOKED) {
            return redirect()->route('admin.compliance.bookings.show', $booking);
        }
        $filters = $request->only(['distance', 'badges', 'only']);
        $result = $this->bookings->find($booking, (int) $booking->account_id, $filters);

        return view('backend.compliance.bookings.find', $result + [
            'booking' => $booking->loadMissing('property', 'type'),
            'filters' => $filters,
            'trades' => ContractorCompliance::trades(),
        ]);
    }

    public function send(Request $request, ComplianceBooking $booking)
    {
        $this->authorizeBooking($booking);
        $data = $request->validate([
            'contractor_ids' => ['nullable', 'array'],
            'contractor_ids.*' => ['integer', 'exists:users,id'],
            'respond_by' => ['nullable', 'date', 'after:now'],
            'access_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'respond_by.after' => 'The respond-by time must be in the future.',
        ]);

        try {
            $result = $this->bookings->send($booking, $data['contractor_ids'] ?? [], $request->user(), $data);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $message = $result['sent'] === [] ? 'Nothing was sent.' : 'Sent to '.implode(', ', $result['sent']).'.';
        if ($result['skipped'] !== []) {
            $message .= ' Not sent: '.implode(' ', $result['skipped']);
        }
        if ($result['sent'] === []) {
            return back()->with('error', $message);
        }

        return redirect()->route('admin.compliance.bookings.show', $booking)->with('success', $message);
    }

    public function invite(Request $request, ComplianceBooking $booking)
    {
        $this->authorizeBooking($booking);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        $result = $this->directory->invite((int) $booking->account_id, $data['email'], $data['name'] ?? null, $request->user());

        return back()->with($result['status'], $result['message']);
    }

    public function show(ComplianceBooking $booking)
    {
        $this->authorizeBooking($booking);
        $this->bookings->expire($booking);
        $booking->load(['property', 'type', 'offers.contractor.contractorProfile', 'awardedOffer.contractor.contractorProfile']);
        $quotes = $booking->offers->filter(fn (ComplianceJobOffer $offer) => in_array($offer->status, [ComplianceJobOffer::OFFER_SENT, ComplianceJobOffer::OFFER_EXPIRED, ComplianceJobOffer::JOB_ACCEPTED], true))->values();

        return view('backend.compliance.bookings.show', [
            'booking' => $booking,
            'quotes' => $quotes,
            'waiting' => $booking->offers->filter(fn (ComplianceJobOffer $offer) => $offer->isAwaitingResponse())->values(),
        ]);
    }

    public function accept(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:0']]);

        try {
            $this->bookings->accept($offer, $request->user(), (int) $data['revision']);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.compliance.bookings.show', $booking)->with('success', 'Check booked. The contractor has the address.');
    }

    private function accountId(): int
    {
        abort_if(is_tenant_portal_user(), 403);
        abort_unless(WorkspaceAccess::canManageCurrentWorkspace(request()->user()), 403);
        $accountId = (int) current_account_id();
        abort_unless($accountId, 403);

        return $accountId;
    }

    private function authorizeBooking(ComplianceBooking $booking): void
    {
        abort_unless((int) $booking->account_id === $this->accountId(), 404);
    }
}
