<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContractorProfile;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\RepairJobVariation;
use App\Models\Upload;
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

        $all = RepairJobOffer::query()
            ->with(['repairIssue.property', 'repairIssue.repairCategory', 'request'])
            ->where('contractor_user_id', $request->user()->id)
            ->latest('updated_at')
            ->limit(300)
            ->get();
        $grouped = $all->groupBy(fn (RepairJobOffer $offer) => $offer->tab());
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
        $offer->refresh()->load(['messages', 'variations', 'invoice']);
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
