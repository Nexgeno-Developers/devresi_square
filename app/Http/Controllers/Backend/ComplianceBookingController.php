<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ComplianceBooking;
use App\Models\ComplianceJobMessage;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceJobPayment;
use App\Models\ComplianceJobVariation;
use App\Models\ComplianceType;
use App\Models\Property;
use App\Services\Contractors\ComplianceBookingService;
use App\Services\Contractors\ComplianceJobAftercareService;
use App\Services\Contractors\ComplianceJobPaymentService;
use App\Services\Contractors\ContractorDirectory;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Support\ContractorCompliance;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplianceBookingController extends Controller
{
    public function __construct(
        private readonly ComplianceBookingService $bookings,
        private readonly ContractorDirectory $directory,
        private readonly ComplianceJobPaymentService $payments,
        private readonly ComplianceJobAftercareService $aftercare,
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
        if (in_array($booking->status, [ComplianceBooking::BOOKED, ComplianceBooking::COMPLETED], true)) {
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
        $booking->load(['property', 'type', 'offers.contractor.contractorProfile', 'offers.certificate', 'offers.invoice', 'offers.messages', 'offers.variations', 'awardedOffer.contractor.contractorProfile', 'awardedOffer.certificate', 'awardedOffer.invoice', 'awardedOffer.messages', 'awardedOffer.variations']);
        $booking->offers->each(function (ComplianceJobOffer $offer) {
            if ($offer->messages->isNotEmpty()) {
                $this->aftercare->markRead($offer, ComplianceJobMessage::LANDLORD);
            }
        });
        $quotes = $booking->offers->filter(fn (ComplianceJobOffer $offer) => in_array($offer->status, [ComplianceJobOffer::OFFER_SENT, ComplianceJobOffer::OFFER_EXPIRED, ComplianceJobOffer::JOB_ACCEPTED, ComplianceJobOffer::CANCELLED], true))->values();

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

    public function confirm(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);

        try {
            $this->bookings->confirm($offer, $request->user());
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.compliance.bookings.show', $booking)->with('success', 'Check confirmed. The contractor has been told.');
    }

    public function invoice(ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);
        $offer->load(['invoice.cardPayment', 'invoice.contractor.contractorProfile', 'booking.type', 'booking.property', 'contractor.contractorProfile']);
        abort_unless($offer->invoice, 404);

        return view('backend.compliance.bookings.invoice', [
            'booking' => $booking,
            'offer' => $offer,
            'invoice' => $offer->invoice,
            'profile' => $offer->contractor?->contractorProfile,
            'jobLabel' => $booking->type?->name ?: 'Compliance check',
            'charge' => $this->payments->chargeFor($offer->invoice),
            'cardPaymentsReady' => $this->payments->isConfigured(),
        ]);
    }

    public function invoicePay(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);
        $invoice = $offer->invoice;
        abort_unless($invoice, 404);

        return redirect()->away($this->payments->createCheckoutUrl($request->user(), $invoice));
    }

    public function invoiceReturn(ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);
        $invoice = $offer->invoice;
        abort_unless($invoice, 404);
        $back = redirect()->route('admin.compliance.bookings.invoice', [$booking, $offer]);

        $sessionId = (string) ($invoice->cardPayment?->stripe_checkout_session_id ?? '');
        if ($sessionId === '' || ! str_starts_with($sessionId, 'cs_')) {
            return $back->with('error', 'We could not confirm that payment. If you were charged, it will show on this page shortly.');
        }

        try {
            $payment = $this->payments->fulfillCheckoutSessionId($sessionId);
        } catch (\Throwable $exception) {
            report($exception);

            return $back->with('error', 'Your card payment is processing. Refresh this page in a moment if the invoice is still unpaid.');
        }

        if ($payment && $payment->status === ComplianceJobPayment::PAID) {
            return $back->with('success', 'Payment received. Resisquare will pay the contractor.');
        }

        return $back->with('error', 'Your card payment is processing. Refresh this page in a moment if the invoice is still unpaid.');
    }

    public function message(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->ownsOffer($booking, $offer);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->after(fn () => $this->aftercare->postMessage($offer, $request->user(), ComplianceJobMessage::LANDLORD, $data['body']), 'Message sent.');
    }

    public function decideVariation(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer, ComplianceJobVariation $variation)
    {
        $this->ownsOffer($booking, $offer);
        abort_unless((int) $variation->compliance_job_offer_id === (int) $offer->id, 404);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'decline'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->after(
            fn () => $this->aftercare->decideVariation($variation, $request->user(), $data['decision'] === 'approve', $data['note'] ?? null),
            $data['decision'] === 'approve' ? 'Extra work approved. The contractor has been told.' : 'Extra work declined.'
        );
    }

    public function reschedule(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->ownsOffer($booking, $offer);
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1', 'max:3'],
            'slots.*' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->after(fn () => $this->aftercare->requestReschedule($offer, $request->user(), $data['slots'], $data['note'] ?? null), 'New times sent to the contractor.');
    }

    public function cancel(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->ownsOffer($booking, $offer);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        return $this->after(fn () => $this->aftercare->cancel($offer, $request->user(), ComplianceJobMessage::LANDLORD, $data['reason'] ?? null), 'Check cancelled. You can send it to another contractor.');
    }

    public function dispute(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->ownsOffer($booking, $offer);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        return $this->after(fn () => $this->aftercare->dispute($offer, $request->user(), ComplianceJobMessage::LANDLORD, $data['reason']), 'Dispute sent. Our team has been told.');
    }

    public function rate(Request $request, ComplianceBooking $booking, ComplianceJobOffer $offer)
    {
        $this->ownsOffer($booking, $offer);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'rating_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->after(fn () => $this->aftercare->rate($offer, $request->user(), (int) $data['rating'], $data['rating_comment'] ?? null), 'Thanks for rating the contractor.');
    }

    private function ownsOffer(ComplianceBooking $booking, ComplianceJobOffer $offer): void
    {
        $this->authorizeBooking($booking);
        abort_unless((int) $offer->compliance_booking_id === (int) $booking->id, 404);
    }

    private function after(callable $action, string $success)
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
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
