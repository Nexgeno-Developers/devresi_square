<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ComplianceJobMessage;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceJobVariation;
use App\Models\ContractorProfile;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\RepairJobVariation;
use App\Models\Upload;
use App\Services\Contractors\ComplianceBookingService;
use App\Services\Contractors\ComplianceJobAftercareService;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Services\Contractors\RepairJobOfferService;
use App\Services\SecureUploadService;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContractorJobController extends Controller
{
    public const TABS = [
        'new' => 'New',
        'offer_sent' => 'Offer sent',
        'awarded' => 'Awarded',
        'completed' => 'Completed',
        'closed' => 'Closed',
    ];

    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly RepairJobAftercareService $aftercare,
        private readonly ComplianceBookingService $checks,
        private readonly ComplianceJobAftercareService $complianceAftercare,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('Contractor'), 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $profile = $request->user()->contractorProfile;
        if ($profile && $profile->approval_status === ContractorProfile::SUBMITTED) {
            return view('frontend.contractor.jobs.index', [
                'profile' => $profile,
                'pendingReview' => true,
                'tabs' => self::TABS,
                'trades' => collect($profile->trades ?? [])
                    ->map(fn ($trade) => ContractorCompliance::trades()[$trade] ?? $trade)
                    ->values(),
                'sla' => (int) ContractorSettings::get('review_sla_working_days'),
            ]);
        }

        if (! $profile || $profile->approval_status !== ContractorProfile::APPROVED) {
            return redirect()->route('contractor.profile.edit');
        }

        $open = RepairJobOffer::query()
            ->where('contractor_user_id', $request->user()->id)
            ->whereIn('status', RepairJobOffer::OPEN_STATUSES)
            ->with('repairIssue')
            ->get();
        $open->pluck('repairIssue')->filter()->unique('id')->each(fn ($repair) => $this->offers->expireStale($repair));

        ComplianceJobOffer::query()
            ->where('contractor_user_id', $request->user()->id)
            ->whereIn('status', ComplianceJobOffer::OPEN_STATUSES)
            ->with('booking')
            ->get()
            ->pluck('booking')
            ->filter()
            ->unique('id')
            ->each(fn ($booking) => $this->checks->expire($booking));

        $all = RepairJobOffer::query()
            ->with(['repairIssue.property', 'repairIssue.repairCategory', 'request'])
            ->where('contractor_user_id', $request->user()->id)
            ->latest('updated_at')
            ->limit(300)
            ->get()
            ->concat(
                ComplianceJobOffer::query()
                    ->with(['booking.property', 'booking.type'])
                    ->where('contractor_user_id', $request->user()->id)
                    ->latest('updated_at')
                    ->limit(300)
                    ->get()
            )
            ->sortByDesc('updated_at')
            ->values();
        $grouped = $all->groupBy(fn ($offer) => $offer->tab());
        $counts = collect(self::TABS)->map(fn ($label, $key) => $grouped->get($key, collect())->count());
        $tab = $request->query('tab');
        if (! array_key_exists((string) $tab, self::TABS)) {
            $tab = $counts['new'] ? 'new' : ($counts['awarded'] ? 'awarded' : ($counts['offer_sent'] ? 'offer_sent' : 'new'));
        }
        $items = $grouped->get($tab, collect());
        if ($tab === 'new') {
            $items = $items->sortBy('invite_expires_at')->values();
        }

        return view('frontend.contractor.jobs.index', [
            'profile' => $profile,
            'tabs' => self::TABS,
            'tab' => $tab,
            'counts' => $counts,
            'items' => $items,
            'paused' => ContractorCompliance::profileBlock($profile),
            'expiring' => ContractorCompliance::nextExpiry($profile),
            'declineReasons' => config('contractor_marketplace.decline_reasons'),
            'pendingReview' => false,
        ]);
    }

    public function show(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $offer->load(['repairIssue.property', 'repairIssue.repairCategory.parentCategory', 'repairIssue.repairPhotos', 'repairIssue.tenant', 'request', 'events']);
        $this->offers->expireStale($offer->repairIssue);
        $offer->refresh()->load(['messages', 'variations', 'invoice.cardPayment']);
        $this->offers->markViewed($offer);
        $this->aftercare->markRead($offer, RepairJobMessage::CONTRACTOR);

        $profile = $request->user()->contractorProfile;
        $repair = $offer->repairIssue;
        $trade = ContractorCompliance::requiredTrade($repair);
        $block = $profile ? ContractorCompliance::blockReason($profile, $trade) : 'Complete your profile first.';
        if ($block && in_array($offer->status, RepairJobOffer::OPEN_STATUSES, true)) {
            $this->offers->flagCompliance($offer, $block);
        }

        return view('frontend.contractor.jobs.show', [
            'offer' => $offer,
            'profile' => $profile,
            'repair' => $repair,
            'property' => $repair->property,
            'request' => $offer->request,
            'unlocked' => $offer->unlocksAccess(),
            'block' => $block,
            'summary' => $offer->pricing_type ? $this->offers->priceSummary($offer) : null,
            'declineReasons' => config('contractor_marketplace.decline_reasons'),
            'availabilityChanged' => $offer->availability_snapshot !== null
                && $repair->tenant_availability !== null
                && $offer->availability_snapshot->toDateTimeString() !== $repair->tenant_availability->toDateTimeString(),
        ]);
    }

    public function quote(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate([
            'pricing_type' => ['required', Rule::in(array_keys(RepairJobOffer::PRICING_TYPES))],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'call_out_fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'price_cap' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'inspection_fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'materials' => ['required', Rule::in(['included', 'excluded', 'estimate'])],
            'materials_estimate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'vat_rate' => ['nullable', Rule::in(['20', '5', '0'])],
            'visit_slots' => ['nullable', 'array'],
            'visit_slots.*' => ['string'],
            'proposed_slot' => ['nullable', 'date'],
            'valid_hours' => ['nullable', Rule::in(['24', '48', '72', '168'])],
            'offer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->run($request, $offer, fn () => $this->offers->submitOffer($offer, $request->user(), $data), 'Offer sent. We’ll tell you as soon as the landlord decides.');
    }

    public function decline(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate([
            'decline_code' => ['nullable', Rule::in(array_keys(config('contractor_marketplace.decline_reasons')))],
            'decline_reason' => ['nullable', 'string', 'max:500'],
        ]);
        try {
            $this->offers->decline($offer, $request->user(), $data['decline_code'] ?? null, $data['decline_reason'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.index')->with('success', 'Declined.');
    }

    public function withdraw(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate(['withdraw_reason' => ['required', 'string', 'max:500']]);

        return $this->run($request, $offer, fn () => $this->offers->withdrawOffer($offer, $request->user(), $data['withdraw_reason']), 'Offer withdrawn.');
    }

    public function reconfirm(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);

        return $this->run($request, $offer, fn () => $this->offers->reconfirm($offer, $request->user()), 'Reconfirmed. The landlord can accept now.');
    }

    public function cancel(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->run($request, $offer, fn () => $this->offers->cancel($offer, $request->user(), 'contractor', $data['reason']), 'Job cancelled. The landlord has been told.');
    }

    public function progress(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate([
            'step' => ['required', Rule::in(['schedule', 'start', 'complete'])],
            'scheduled_for' => ['required_if:step,schedule', 'nullable', 'date'],
            'completion_note' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required_if:step,complete', 'array', 'min:1', 'max:'.RepairJobAftercareService::MAX_PHOTOS],
            'photos.*' => ['image', 'max:8192'],
        ], [
            'photos.required_if' => 'Add a photo of the finished work before marking the job complete.',
            'photos.min' => 'Add a photo of the finished work before marking the job complete.',
            'photos.max' => 'Add up to '.RepairJobAftercareService::MAX_PHOTOS.' photos.',
            'photos.*.image' => 'Photos must be images.',
            'photos.*.max' => 'Each photo must be under 8 MB.',
        ]);
        $messages = ['schedule' => 'Visit booked. The landlord and tenant have been told.', 'start' => 'Marked as in progress.', 'complete' => 'Marked complete. The landlord will confirm.'];
        $action = $data['step'] === 'complete'
            ? fn () => $this->aftercare->complete($offer, $request->user(), $data['completion_note'] ?? null, $request->file('photos', []))
            : fn () => $this->offers->progress($offer, $request->user(), $data['step'], $data);

        return $this->run($request, $offer, $action, $messages[$data['step']]);
    }

    public function message(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->run($request, $offer, fn () => $this->aftercare->postMessage($offer, $request->user(), RepairJobMessage::CONTRACTOR, $data['body']), 'Sent to the landlord.', '#messages');
    }

    public function variation(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate([
            'net_amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->run($request, $offer, fn () => $this->aftercare->requestVariation($offer, $request->user(), (float) $data['net_amount'], $data['reason']), 'Sent. Don’t start the extra work until the landlord approves it.');
    }

    public function withdrawVariation(Request $request, RepairJobOffer $offer, RepairJobVariation $variation)
    {
        $this->owns($request, $offer);
        abort_unless((int) $variation->repair_job_offer_id === (int) $offer->id, 404);

        return $this->run($request, $offer, fn () => $this->aftercare->withdrawVariation($variation, $request->user()), 'Extra work request withdrawn.');
    }

    public function reschedule(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate(['slot' => ['required', 'date']]);

        return $this->run($request, $offer, fn () => $this->aftercare->reschedule($offer, $request->user(), $data['slot']), 'Visit moved. The landlord and tenant have been told.');
    }

    public function completionPhoto(Request $request, RepairJobOffer $offer, int $index)
    {
        $this->owns($request, $offer);
        $path = ($offer->completion_photos ?? [])[$index] ?? null;
        abort_unless($path && Storage::disk(RepairJobAftercareService::PHOTO_DISK)->exists($path), 404);

        return Storage::disk(RepairJobAftercareService::PHOTO_DISK)->response($path);
    }

    public function invoice(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $offer->load(['invoice', 'variations', 'repairIssue.property', 'repairIssue.repairCategory', 'contractor.contractorProfile']);
        abort_unless($offer->invoice, 404);

        return view('frontend.contractor.jobs.invoice', [
            'offer' => $offer,
            'invoice' => $offer->invoice,
            'profile' => $offer->contractor?->contractorProfile,
            'summary' => $this->offers->priceSummary($offer),
        ]);
    }

    public function issueInvoice(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate([
            'final_net' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        try {
            $this->aftercare->issueInvoice($offer, $request->user(), isset($data['final_net']) ? (float) $data['final_net'] : null, $data['notes'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.invoice', $offer)->with('success', 'Invoice sent to the landlord.');
    }

    public function dispute(Request $request, RepairJobOffer $offer)
    {
        $this->owns($request, $offer);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return $this->run($request, $offer, fn () => $this->offers->dispute($offer, $request->user(), 'contractor', $data['reason']), 'Flagged. Our team will be in touch.');
    }

    public function photo(Request $request, RepairJobOffer $offer, int $upload)
    {
        $this->owns($request, $offer);
        $repair = $offer->repairIssue()->with('repairPhotos')->firstOrFail();
        $ids = $repair->repairPhotos
            ->flatMap(fn ($photo) => array_filter(array_map('trim', explode(',', (string) $photo->photos))))
            ->map(fn ($id) => (int) $id);
        abort_unless($ids->contains($upload), 404);

        $file = Upload::query()->findOrFail($upload);
        $service = app(SecureUploadService::class);
        $stored = (string) $file->file_name;
        $relative = $service->resolveRelativePath($stored);
        $disk = $service->resolveDisk($stored);

        if (Storage::disk($disk)->exists($relative)) {
            return Storage::disk($disk)->response(
                $relative,
                ($file->file_original_name ?: 'photo').($file->extension ? '.'.$file->extension : '')
            );
        }

        $legacy = public_path($stored);
        abort_unless(is_file($legacy), 404);

        return response()->file($legacy);
    }

    public function complianceShow(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $offer->load(['booking.property', 'booking.type', 'certificate', 'invoice', 'messages', 'variations', 'contractor.contractorProfile']);
        $this->checks->expire($offer->booking);
        $offer->refresh()->load(['booking.property', 'booking.type', 'certificate', 'invoice', 'messages', 'variations', 'contractor.contractorProfile']);
        $this->checks->markViewed($offer);
        $offer->refresh()->load(['booking.property', 'booking.type', 'certificate', 'invoice', 'messages', 'variations', 'contractor.contractorProfile']);
        if ($offer->canMessage()) {
            $this->complianceAftercare->markRead($offer, ComplianceJobMessage::CONTRACTOR);
        }

        return view('frontend.contractor.jobs.compliance', ['offer' => $offer]);
    }

    public function complianceQuote(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate([
            'pricing_type' => ['required', 'in:fixed,hourly,callout_hourly'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'call_out_fee' => ['nullable', 'numeric', 'min:0'],
            'materials' => ['nullable', 'in:included,excluded,estimate'],
            'materials_estimate' => ['nullable', 'numeric', 'min:0'],
            'offer_note' => ['nullable', 'string', 'max:2000'],
            'visit_slots' => ['required', 'array', 'min:1'],
            'visit_slots.*' => ['required', 'date'],
        ]);

        try {
            $this->checks->quote($offer, $request->user(), $data);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.compliance.show', $offer)->with('success', 'Quote sent.');
    }

    public function complianceDecline(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate([
            'decline_code' => ['nullable', 'string', 'max:24'],
            'decline_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->checks->decline($offer, $request->user(), $data['decline_code'] ?? null, $data['decline_reason'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.index')->with('success', 'Invitation declined.');
    }

    public function complianceProgress(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate([
            'step' => ['required', Rule::in(['schedule', 'start', 'complete'])],
            'scheduled_for' => ['required_if:step,schedule', 'nullable', 'date'],
            'completion_note' => ['nullable', 'string', 'max:2000'],
            'certificate' => ['required_if:step,complete', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'issued_date' => ['required_if:step,complete', 'nullable', 'date'],
            'expiry_date' => ['required_if:step,complete', 'nullable', 'date', 'after_or_equal:issued_date'],
        ], [
            'certificate.required_if' => 'Attach the certificate before marking the check complete.',
            'issued_date.required_if' => 'Enter the date on the certificate.',
            'expiry_date.required_if' => 'Enter the expiry date on the certificate.',
            'expiry_date.after_or_equal' => 'The expiry date must be on or after the issued date.',
        ]);
        if ($data['step'] === 'complete') {
            $data['certificate'] = $request->file('certificate');
        }
        $messages = [
            'schedule' => 'Visit updated. The landlord has been told.',
            'start' => 'Marked as in progress.',
            'complete' => 'Marked complete. The certificate is filed and the landlord will confirm.',
        ];

        try {
            $this->checks->progress($offer, $request->user(), $data['step'], $data);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.compliance.show', $offer)->with('success', $messages[$data['step']]);
    }

    public function complianceInvoice(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $offer->load(['invoice.cardPayment', 'booking.type', 'booking.property', 'contractor.contractorProfile']);
        abort_unless($offer->invoice, 404);

        return view('frontend.contractor.jobs.compliance-invoice', [
            'offer' => $offer,
            'invoice' => $offer->invoice,
            'profile' => $offer->contractor?->contractorProfile,
            'jobLabel' => $offer->booking?->type?->name ?: 'Compliance check',
        ]);
    }

    public function complianceIssueInvoice(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->checks->issueInvoice($offer, $request->user(), $data['notes'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.compliance.invoice', $offer)->with('success', 'Invoice sent to the landlord.');
    }

    public function complianceMessage(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->postMessage($offer, $request->user(), ComplianceJobMessage::CONTRACTOR, $data['body']), 'Message sent.');
    }

    public function complianceVariation(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->requestVariation($offer, $request->user(), (float) $data['amount'], $data['reason']), 'Extra work sent to the landlord.');
    }

    public function complianceWithdrawVariation(Request $request, ComplianceJobOffer $offer, ComplianceJobVariation $variation)
    {
        $this->ownsCompliance($request, $offer);
        abort_unless((int) $variation->compliance_job_offer_id === (int) $offer->id, 404);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->withdrawVariation($variation, $request->user()), 'Extra work request withdrawn.');
    }

    public function complianceReschedule(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate(['slot' => ['required', 'date']]);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->acceptReschedule($offer, $request->user(), $data['slot']), 'Visit time accepted. The landlord has been told.');
    }

    public function complianceCancel(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->cancel($offer, $request->user(), ComplianceJobMessage::CONTRACTOR, $data['reason'] ?? null), 'Check cancelled. The landlord has been told.');
    }

    public function complianceDispute(Request $request, ComplianceJobOffer $offer)
    {
        $this->ownsCompliance($request, $offer);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        return $this->complianceBack($request, $offer, fn () => $this->complianceAftercare->dispute($offer, $request->user(), ComplianceJobMessage::CONTRACTOR, $data['reason']), 'Dispute sent. Our team has been told.');
    }

    private function complianceBack(Request $request, ComplianceJobOffer $offer, callable $action, string $success)
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('contractor.jobs.compliance.show', $offer)->with('success', $success);
    }

    private function ownsCompliance(Request $request, ComplianceJobOffer $offer): void
    {
        abort_unless((int) $offer->contractor_user_id === (int) $request->user()->id, 403);

        $profile = $request->user()->contractorProfile;
        if (! $profile || $profile->approval_status !== ContractorProfile::APPROVED) {
            $target = $profile?->approval_status === ContractorProfile::SUBMITTED
                ? route('contractor.jobs.index')
                : route('contractor.profile.edit');
            throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($target));
        }
    }

    private function run(Request $request, RepairJobOffer $offer, callable $action, string $success, string $anchor = '')
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route('contractor.jobs.show', $offer).$anchor)->with('success', $success);
    }

    private function owns(Request $request, RepairJobOffer $offer): void
    {
        abort_unless((int) $offer->contractor_user_id === (int) $request->user()->id, 403);

        $profile = $request->user()->contractorProfile;
        if (! $profile || $profile->approval_status !== ContractorProfile::APPROVED) {
            $target = $profile?->approval_status === ContractorProfile::SUBMITTED
                ? route('contractor.jobs.index')
                : route('contractor.profile.edit');

            throw new HttpResponseException(redirect($target));
        }
    }
}
