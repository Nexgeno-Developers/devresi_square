<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContractorCoverageGap;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\RepairContractorRequest;
use App\Models\RepairJobOffer;
use App\Models\Registration;
use App\Models\User;
use App\Services\Contractors\ContractorDirectory;
use App\Services\Contractors\ContractorDocumentStore;
use App\Services\Contractors\ContractorMail;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobOfferService;
use App\Support\AuditTrail;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContractorReviewController extends Controller
{
    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly ContractorDocumentStore $store,
        private readonly ContractorMail $mail,
        private readonly ContractorDirectory $directory,
    ) {
        $this->middleware(['role:Super Admin', 'permission:manage registrations']);
    }

    // ── Queue and list ──────────────────────────────────────────────────────

    public function index()
    {
        $profiles = ContractorProfile::query()
            ->with(['user', 'documents', 'tradeApprovals'])
            ->where(function ($query) {
                $query->where('approval_status', ContractorProfile::SUBMITTED)
                    ->orWhere(function ($approved) {
                        $approved->where('approval_status', ContractorProfile::APPROVED)
                            ->where(fn ($pending) => $pending
                                ->whereHas('documents', fn ($docs) => $docs->where('status', ContractorDocument::PENDING))
                                ->orWhereHas('tradeApprovals', fn ($trades) => $trades->where('status', ContractorTrade::PENDING)));
                    });
            })
            ->get()
            ->map(function (ContractorProfile $profile) {
                $since = $profile->approval_status === ContractorProfile::SUBMITTED
                    ? $profile->submitted_at
                    : $profile->documents->where('status', ContractorDocument::PENDING)->min('submitted_at');
                $since = $since ? Carbon::parse($since) : $profile->updated_at;

                return (object) [
                    'profile' => $profile,
                    'since' => $since,
                    'age' => $this->workingDaysSince($since),
                    'pendingDocs' => $profile->documents->where('status', ContractorDocument::PENDING)->count(),
                    'pendingTrades' => $profile->tradeApprovals->where('status', ContractorTrade::PENDING)->count(),
                ];
            })
            ->sortBy(fn ($row) => $row->since?->timestamp ?? 0)
            ->values();

        return view('backend.contractors.review-index', [
            'rows' => $profiles,
            'sla' => (int) ContractorSettings::get('review_sla_working_days'),
            'waitingOnContractor' => ContractorProfile::query()->where('approval_status', ContractorProfile::CHANGES)->count(),
        ]);
    }

    public function listContractors(Request $request)
    {
        $query = ContractorProfile::query()->with(['user', 'documents', 'tradeApprovals'])->latest('id');
        if ($request->filled('status')) {
            $query->where('approval_status', $request->string('status')->toString());
        }
        if ($request->filled('trade')) {
            $query->whereJsonContains('trades', $request->string('trade')->toString());
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(fn ($q) => $q->where('trading_name', 'like', $term)
                ->orWhere('legal_name', 'like', $term)
                ->orWhere('postcode', 'like', $term)
                ->orWhere('company_number', 'like', $term)
                ->orWhereHas('user', fn ($user) => $user->where('email', 'like', $term)->orWhere('name', 'like', $term)));
        }
        $profiles = $query->paginate(30)->withQueryString();

        return view('backend.contractors.directory', [
            'profiles' => $profiles,
            'performance' => $this->directory->performance($profiles->pluck('user_id')->map(fn ($id) => (int) $id)->all()),
            'legacyPending' => ContractorProfile::query()->where('legacy_contractor', true)->whereNull('terms_accepted_at')->count(),
        ]);
    }

    public function show(ContractorProfile $profile)
    {
        $profile->load(['user', 'documents.profile', 'tradeApprovals', 'reviewEvents.actor', 'reviewer']);

        return view('backend.contractors.review-show', [
            'profile' => $profile,
            'required' => ContractorCompliance::requiredDocuments($profile),
            'optional' => ContractorCompliance::optionalDocuments($profile),
            'gaps' => ContractorCompliance::gaps($profile),
            'ready' => $this->approvalBlock($profile),
            'performance' => $this->directory->performance([(int) $profile->user_id])->get((int) $profile->user_id),
            'rejectReasons' => config('contractor_marketplace.reject_reasons'),
            'store' => $this->store,
            'duplicates' => $profile->company_number
                ? ContractorProfile::query()->where('company_number', $profile->company_number)->where('id', '!=', $profile->id)->with('user')->get()
                : collect(),
        ]);
    }

    public function document(ContractorProfile $profile, ContractorDocument $document)
    {
        abort_unless((int) $document->contractor_profile_id === (int) $profile->id, 404);
        $profile->log('document_viewed', request()->user(), $document->type);

        return $this->store->download($document);
    }

    // ── Per-document and per-trade decisions ────────────────────────────────

    public function decideDocument(Request $request, ContractorProfile $profile, ContractorDocument $document)
    {
        abort_unless((int) $document->contractor_profile_id === (int) $profile->id, 404);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'expires_at' => ['nullable', 'date'],
            'cover_amount' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'issuer' => ['nullable', 'string', 'max:120'],
            'reason_code' => ['nullable', Rule::in(array_keys(config('contractor_marketplace.reject_reasons')))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $baseType = str_replace(ContractorDocumentStore::RENEWAL, '', $document->type);
        $spec = ContractorCompliance::documentSpec($baseType);
        $label = $spec['label'] ?? $baseType;

        if ($data['decision'] === 'reject') {
            $reason = trim(implode(': ', array_filter([
                $data['reason_code'] && $data['reason_code'] !== 'other' ? config('contractor_marketplace.reject_reasons.'.$data['reason_code']) : null,
                $data['reason'] ?? null,
            ])));
            if ($reason === '') {
                return back()->with('error', 'Pick or type a reason.');
            }
            $document->forceFill([
                'status' => ContractorDocument::REJECTED,
                'rejected_reason' => $reason,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ])->save();
            $profile->log('document_rejected', $request->user(), $document->type, $reason);
            if ($profile->approval_status === ContractorProfile::APPROVED) {
                $this->tell($profile->user, $label.' needs another upload', '<p>We could not accept your '.e(strtolower($label)).': '.e($reason).'.</p><p><a href="'.e(route('contractor.profile.edit')).'">Upload it again</a>.</p>', 'We could not accept your '.strtolower($label).': '.$reason, route('contractor.profile.edit'));
            }

            return back()->with('success', $label.' rejected.');
        }

        $attributes = array_filter([
            'expires_at' => $data['expires_at'] ?? null,
            'cover_amount' => $data['cover_amount'] ?? null,
            'reference' => $data['reference'] ?? null,
            'issuer' => $data['issuer'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $document->fill($attributes);
        if (($spec['expires'] ?? false) && ! $document->expires_at) {
            return back()->with('error', 'Add the expiry date before approving '.$label.'.');
        }
        if ($document->expires_at && $document->isExpired()) {
            return back()->with('error', $label.' has already expired.');
        }
        if (($spec['reference'] ?? false) && blank($document->reference)) {
            return back()->with('error', 'Add the registration number before approving '.$label.'.');
        }
        $minimum = match ($baseType) {
            ContractorDocument::PUBLIC_LIABILITY => ContractorSettings::plMinimum(),
            ContractorDocument::EMPLOYERS_LIABILITY => ContractorSettings::elMinimum(),
            default => null,
        };
        if ($minimum !== null && (float) $document->cover_amount < $minimum) {
            return back()->with('error', $label.' cover must be at least £'.number_format($minimum, 0).'.');
        }
        $document->forceFill([
            'status' => ContractorDocument::APPROVED,
            'rejected_reason' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ])->save();
        if ($document->type !== $baseType) {
            $document = $this->store->promote($document);
        }
        $profile->log('document_approved', $request->user(), $baseType, null, $attributes);
        $this->afterComplianceChange($profile);

        return back()->with('success', $label.' approved.');
    }

    public function decideTrade(Request $request, ContractorProfile $profile, string $trade)
    {
        $approval = $profile->tradeApproval($trade);
        abort_unless($approval, 404);
        $data = $request->validate([
            'decision' => ['required', Rule::in([ContractorTrade::APPROVED, ContractorTrade::MORE_INFO, ContractorTrade::REJECTED])],
            'reason' => ['nullable', 'string', 'max:500', 'required_unless:decision,approved'],
        ]);
        $label = ContractorCompliance::trades()[$trade] ?? $trade;
        if ($data['decision'] === ContractorTrade::APPROVED) {
            $profile->load('documents');
            foreach (ContractorCompliance::documentsForTrade($trade) as $type) {
                if ($problem = ContractorCompliance::documentProblem($profile, $type)) {
                    return back()->with('error', 'Approve the trade documents first. '.$problem);
                }
            }
        }
        $approval->forceFill([
            'status' => $data['decision'],
            'reason' => $data['reason'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        $profile->log('trade_'.$data['decision'], $request->user(), $trade, $data['reason'] ?? null);
        if ($profile->approval_status === ContractorProfile::APPROVED) {
            $approved = $data['decision'] === ContractorTrade::APPROVED;
            $this->tell(
                $profile->user,
                $label.': '.($approved ? 'approved' : 'not approved yet'),
                $approved
                    ? '<p>You can now receive '.e(strtolower($label)).' jobs.</p>'
                    : '<p>'.e($data['reason'] ?? '').'</p><p><a href="'.e(route('contractor.profile.edit')).'">Open your documents</a>.</p>',
                $approved ? 'You can now receive '.strtolower($label).' jobs.' : ($data['reason'] ?? 'This trade is not approved yet.'),
                $approved ? route('contractor.jobs.index') : route('contractor.profile.edit')
            );
            $this->offers->withdrawBlocked($profile->fresh(['documents', 'tradeApprovals']));
        }

        return back()->with('success', $label.' marked '.str_replace('_', ' ', $data['decision']).'.');
    }

    // ── Outcomes ────────────────────────────────────────────────────────────

    /**
     * One decision for the whole application: accept every submitted document and trade, or reject with a note.
     */
    public function decidePack(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_note' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject'],
        ]);

        if ($data['decision'] === 'reject') {
            return $this->reject($request, $profile);
        }

        if (! in_array($profile->approval_status, [
            ContractorProfile::SUBMITTED,
            ContractorProfile::CHANGES,
            ContractorProfile::DRAFT,
            ContractorProfile::REJECTED,
        ], true)) {
            return back()->with('error', 'This contractor is already '.$profile->statusLabel().'.');
        }

        $profile->load(['documents', 'tradeApprovals']);
        foreach (ContractorCompliance::requiredDocuments($profile) as $type) {
            if ($gap = ContractorCompliance::uploadGap($profile, $type)) {
                return back()->with('error', $gap);
            }
            $document = $profile->document($type);
            if ($document?->isExpired()) {
                $label = ContractorCompliance::documentSpec($type)['label'] ?? $type;

                return back()->with('error', $label.' expired on '.$document->expires_at->format('j M Y').'.');
            }
            $minimum = match ($type) {
                ContractorDocument::PUBLIC_LIABILITY => ContractorSettings::plMinimum(),
                ContractorDocument::EMPLOYERS_LIABILITY => ContractorSettings::elMinimum(),
                default => null,
            };
            if ($minimum !== null && (float) $document?->cover_amount < $minimum) {
                $label = ContractorCompliance::documentSpec($type)['label'] ?? $type;

                return back()->with('error', $label.' cover must be at least £'.number_format($minimum, 0).'.');
            }
        }

        DB::transaction(function () use ($profile, $request) {
            foreach ($profile->documents as $document) {
                if (in_array($document->status, [ContractorDocument::APPROVED, ContractorDocument::REJECTED, ContractorDocument::PURGED], true)) {
                    continue;
                }
                $document->forceFill([
                    'status' => ContractorDocument::APPROVED,
                    'rejected_reason' => null,
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ])->save();
                $base = str_replace(ContractorDocumentStore::RENEWAL, '', $document->type);
                if (str_ends_with($document->type, ContractorDocumentStore::RENEWAL)) {
                    $document = $this->store->promote($document);
                }
                $profile->log('document_approved', $request->user(), $base);
            }

            foreach ($profile->trades ?? [] as $trade) {
                $approval = $profile->tradeApprovals()->firstOrCreate(
                    ['trade' => $trade],
                    ['status' => ContractorTrade::PENDING]
                );
                if ($approval->status === ContractorTrade::APPROVED) {
                    continue;
                }
                $approval->forceFill([
                    'status' => ContractorTrade::APPROVED,
                    'reason' => null,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ])->save();
                $profile->log('trade_approved', $request->user(), $trade);
            }
        });

        $profile->unsetRelation('documents');
        $profile->unsetRelation('tradeApprovals');

        return $this->approve($request, $profile);
    }

    public function approve(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate([
            'cis_status' => ['nullable', Rule::in(['unverified', 'gross', 'net_20', 'higher_30'])],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $profile->load(['documents', 'tradeApprovals']);
        if ($block = $this->approvalBlock($profile)) {
            return back()->with('error', 'Cannot approve yet. '.$block);
        }
        $beforeStatus = $profile->approval_status;
        $profile->forceFill([
            'approval_status' => ContractorProfile::APPROVED,
            'cis_status' => $data['cis_status'] ?? $profile->cis_status,
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'paused_at' => null,
            'pause_reason' => null,
        ])->save();
        $profile->log('approved', $request->user(), null, $data['review_note'] ?? null, ['trades' => ContractorCompliance::approvedTrades($profile)]);
        AuditTrail::record(null, $request->user()->id, 'contractor_approval', $profile, [
            'approval_status' => $beforeStatus,
        ], [
            'approval_status' => $profile->approval_status,
        ]);
        Registration::query()->where('user_id', $profile->user_id)->where('type', 'contractor')->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $trades = collect(ContractorCompliance::approvedTrades($profile))->map(fn ($t) => ContractorCompliance::trades()[$t] ?? $t)->implode(', ');
        $this->tell($profile->user, 'You’re approved', '<p>You’re approved for: '.e($trades).'. Landlords in your area can now send you repairs.</p><p><a href="'.e(route('contractor.jobs.index')).'">Open your jobs</a></p>', 'You’re approved for: '.$trades.'. Landlords in your area can now send you repairs.', route('contractor.jobs.index'));

        return back()->with('success', 'Approved for '.$trades.'.');
    }

    public function requestChanges(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $profile->load(['documents', 'tradeApprovals']);
        $failed = $profile->documents->where('status', ContractorDocument::REJECTED)
            ->map(fn (ContractorDocument $doc) => $doc->label().': '.$doc->rejected_reason)
            ->merge($profile->tradeApprovals->whereIn('status', [ContractorTrade::MORE_INFO])->map(fn ($t) => $t->label().': '.$t->reason))
            ->values();
        if ($failed->isEmpty() && blank($data['review_note'] ?? null)) {
            return back()->with('error', 'Reject at least one document or trade, or add a note, so the contractor knows what to fix.');
        }
        $profile->forceFill([
            'approval_status' => ContractorProfile::CHANGES,
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        $profile->log('more_info', $request->user(), null, $data['review_note'] ?? null, ['items' => $failed->all()]);
        $list = $failed->map(fn ($line) => '<li>'.e($line).'</li>')->implode('');
        $plain = 'We need a couple of things before we can approve you.'.($failed->isNotEmpty() ? ' '.$failed->implode(' ') : '').(filled($data['review_note'] ?? null) ? ' '.$data['review_note'] : '');
        $this->tell($profile->user, 'A few things to fix',
            '<p>We need a couple of things before we can approve you.</p>'.($list ? '<ul>'.$list.'</ul>' : '').(filled($data['review_note'] ?? null) ? '<p>'.e($data['review_note']).'</p>' : '')
            .'<p>You only need to re-upload these. <a href="'.e(route('contractor.profile.edit')).'">Fix now</a></p>',
            $plain,
            route('contractor.profile.edit'));

        return back()->with('success', 'Sent back for more info.');
    }

    public function reject(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $profile->forceFill([
            'approval_status' => ContractorProfile::REJECTED,
            'review_note' => $data['review_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        $profile->log('rejected', $request->user(), null, $data['review_note']);
        Registration::query()->where('user_id', $profile->user_id)->where('type', 'contractor')->where('status', '!=', 'approved')
            ->update(['status' => 'rejected', 'rejected_at' => now()]);
        $this->offers->withdrawBlocked($profile, 'Contractor is no longer approved.');
        $this->tell($profile->user, 'Your application was not approved', '<p>'.e($data['review_note']).'</p><p>You can update your details and apply again at any time.</p>', $data['review_note'].' You can update your details and apply again at any time.', route('contractor.profile.edit'));

        return back()->with('success', 'Rejected. They can re-apply; history is kept.');
    }

    public function suspend(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $profile->forceFill([
            'approval_status' => ContractorProfile::SUSPENDED,
            'review_note' => $data['review_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        $profile->log('suspended', $request->user(), null, $data['review_note']);
        $this->offers->withdrawOpenForSuspended($profile);
        $this->tell($profile->user, 'Your contractor account is suspended', '<p>'.e($data['review_note']).'</p><p>You won’t get new jobs while suspended. Jobs already accepted stay in place.</p>', $data['review_note'].' You won’t get new jobs while suspended. Jobs already accepted stay in place.', route('contractor.jobs.index'));

        return back()->with('success', 'Suspended and hidden from landlords. Open invitations were withdrawn.');
    }

    public function reinstate(Request $request, ContractorProfile $profile)
    {
        abort_unless(in_array($profile->approval_status, [ContractorProfile::SUSPENDED, ContractorProfile::REMOVED], true), 404);
        $profile->load(['documents', 'tradeApprovals']);
        if ($block = $this->approvalBlock($profile)) {
            return back()->with('error', 'Cannot reinstate yet. '.$block);
        }
        $profile->forceFill([
            'approval_status' => ContractorProfile::APPROVED,
            'removed_at' => null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        $profile->log('reinstated', $request->user());
        $this->tell($profile->user, 'Your contractor account is active again', '<p>You can receive repair jobs again.</p>', 'You can receive repair jobs again.', route('contractor.jobs.index'));

        return back()->with('success', 'Reinstated.');
    }

    public function remove(Request $request, ContractorProfile $profile)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($profile, $data, $request) {
            $profile->forceFill([
                'approval_status' => ContractorProfile::REMOVED,
                'removed_at' => now(),
                'company_number' => null,
                'review_note' => $data['review_note'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        });
        $profile->log('removed', $request->user(), null, $data['review_note']);
        $this->offers->withdrawOpenForSuspended($profile);

        return redirect()->route('admin.registrations.contractors.directory')
            ->with('success', 'Removed. Documents are deleted after '.ContractorSettings::get('document_retention_days').' days; job history stays.');
    }

    // ── Settings, coverage, stuck jobs, legacy ──────────────────────────────

    public function settings()
    {
        $documentTypes = collect(config('contractor_marketplace.documents'))
            ->except(config('contractor_marketplace.base_documents'))
            ->map(fn ($spec) => $spec['label'] ?? '')
            ->all();

        return view('backend.contractors.settings', [
            'values' => collect(array_keys(config('contractor_marketplace.defaults')))->mapWithKeys(fn ($key) => [$key => ContractorSettings::get($key)])->all(),
            'trades' => ContractorCompliance::trades(),
            'documentTypes' => $documentTypes,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $urgencies = array_keys(RepairContractorRequest::URGENCIES);
        $rules = [
            'public_liability_minimum' => ['required', 'integer', 'min:0'],
            'employers_liability_minimum' => ['required', 'integer', 'min:0'],
            'max_contractors_per_send' => ['required', 'integer', 'min:1', 'max:20'],
            'max_contractors_per_emergency_send' => ['required', 'integer', 'min:1', 'max:30'],
            'review_sla_working_days' => ['required', 'integer', 'min:1', 'max:30'],
            'document_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'digest_hours' => ['required', 'integer', 'min:1', 'max:48'],
            'reminder_days' => ['required', 'string', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'emergency_sms' => ['nullable', 'boolean'],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'trade_documents' => ['nullable', 'array'],
            'trade_documents.*' => ['array'],
            'trade_documents.*.*' => [Rule::in(array_keys(config('contractor_marketplace.documents')))],
        ];
        foreach ($urgencies as $urgency) {
            $rules['invite_hours.'.$urgency] = ['required', 'integer', 'min:1', 'max:336'];
            $rules['offer_valid_hours.'.$urgency] = ['required', 'integer', 'min:1', 'max:336'];
        }
        $data = $request->validate($rules);
        $tradeDocs = [];
        foreach (array_keys(ContractorCompliance::trades()) as $trade) {
            $tradeDocs[$trade] = array_values($data['trade_documents'][$trade] ?? []);
        }
        if (! in_array(ContractorDocument::GAS_SAFE, $tradeDocs['gas'] ?? [], true)) {
            return back()->withInput()->with('error', 'Gas work must always require Gas Safe registration.');
        }
        ContractorSettings::put([
            'public_liability_minimum' => $data['public_liability_minimum'],
            'employers_liability_minimum' => $data['employers_liability_minimum'],
            'max_contractors_per_send' => $data['max_contractors_per_send'],
            'max_contractors_per_emergency_send' => $data['max_contractors_per_emergency_send'],
            'review_sla_working_days' => $data['review_sla_working_days'],
            'document_retention_days' => $data['document_retention_days'],
            'digest_hours' => $data['digest_hours'],
            'emergency_sms' => $request->boolean('emergency_sms') ? 1 : 0,
            'invoice_due_days' => $data['invoice_due_days'],
            'reminder_days' => array_values(array_map('intval', array_filter(array_map('trim', explode(',', $data['reminder_days']))))),
            'invite_hours' => array_map('intval', $data['invite_hours']),
            'offer_valid_hours' => array_map('intval', $data['offer_valid_hours']),
            'trade_documents' => $tradeDocs,
        ]);

        return back()->with('success', 'Settings saved.');
    }

    public function coverage()
    {
        $gaps = ContractorCoverageGap::query()
            ->selectRaw('postcode_district, trade, count(*) as total, max(created_at) as last_seen')
            ->where('created_at', '>=', now()->subDays(90))
            ->groupBy('postcode_district', 'trade')
            ->orderByDesc('total')
            ->get();
        $approved = ContractorProfile::query()->with(['documents', 'tradeApprovals'])->where('approval_status', ContractorProfile::APPROVED)->get();
        $perTrade = collect(ContractorCompliance::trades())->map(fn ($label, $trade) => $approved->filter(fn ($p) => ContractorCompliance::canReceiveWork($p, $trade))->count());

        return view('backend.contractors.coverage', ['gaps' => $gaps, 'perTrade' => $perTrade, 'trades' => ContractorCompliance::trades()]);
    }

    public function jobs()
    {
        $disputed = RepairJobOffer::query()->with(['contractor.contractorProfile', 'repairIssue'])->whereNotNull('disputed_at')->latest('disputed_at')->get();
        $overdue = RepairJobOffer::query()->with(['contractor.contractorProfile', 'repairIssue'])
            ->where('status', RepairJobOffer::JOB_ACCEPTED)
            ->whereIn('job_status', [RepairJobOffer::JOB_ACCEPTED_STATE, RepairJobOffer::JOB_SCHEDULED, RepairJobOffer::JOB_IN_PROGRESS])
            ->where(fn ($q) => $q->where('scheduled_for', '<', now()->subDays(7))->orWhere(fn ($q) => $q->whereNull('scheduled_for')->where('decided_at', '<', now()->subDays(7))))
            ->whereNull('disputed_at')
            ->get();
        $noReplies = RepairContractorRequest::query()->with(['repairIssue', 'offers.contractor.contractorProfile'])
            ->whereIn('status', [RepairContractorRequest::SENT, RepairContractorRequest::AWAITING])
            ->where(fn ($q) => $q->where('respond_by', '<', now())->orWhere('status', RepairContractorRequest::AWAITING))
            ->whereDoesntHave('offers', fn ($q) => $q->whereIn('status', RepairJobOffer::OPEN_STATUSES))
            ->latest('updated_at')
            ->limit(100)
            ->get();

        return view('backend.contractors.jobs', compact('disputed', 'overdue', 'noReplies'));
    }

    public function override(Request $request, RepairJobOffer $offer)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['resolve_dispute', 'close', 'cancel_award'])],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        try {
            $this->offers->override($offer, $request->user(), $data['action'], $data['note']);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Done. The action is in the job’s audit trail.');
    }

    public function inviteLegacy(Request $request)
    {
        $sent = $this->directory->sendLegacyClaims($request->user());

        return back()->with('success', $sent ? 'Sent '.$sent.' claim '.str('invitation')->plural($sent).'.' : 'No old-module contractors are waiting for an invitation.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** What stops approval: base documents, cover, and at least one approved trade. */
    private function approvalBlock(ContractorProfile $profile): ?string
    {
        $probe = $profile->replicate();
        $probe->approval_status = ContractorProfile::APPROVED;
        $probe->setRelation('documents', $profile->documents);
        $probe->setRelation('tradeApprovals', $profile->tradeApprovals);

        return ContractorCompliance::blockReason($probe);
    }

    private function afterComplianceChange(ContractorProfile $profile): void
    {
        $profile->load(['documents', 'tradeApprovals']);
        if ($profile->approval_status === ContractorProfile::APPROVED && $profile->paused_at && ! ContractorCompliance::profileBlock($profile)) {
            $profile->forceFill(['paused_at' => null, 'pause_reason' => null])->save();
            $profile->log('unpaused', null, null, 'Compliance back in date.');
            $this->tell($profile->user, 'You’re active again', '<p>Your renewal was approved. Landlords can send you jobs again.</p>', 'Your renewal was approved. Landlords can send you jobs again.', route('contractor.jobs.index'), 'contractor.profile_active');
        }
    }

    private function tell(?User $user, string $subject, string $html, string $plain, ?string $url = null, string $eventKey = 'contractor.review'): void
    {
        $this->mail->toUser($user, $subject, $html);
        $this->mail->bell($user, $subject, $plain, $url, null, $eventKey);
    }

    private function workingDaysSince(?Carbon $since): int
    {
        if (! $since) {
            return 0;
        }
        $days = 0;
        $cursor = $since->copy()->startOfDay()->addDay();
        $today = now()->startOfDay();
        while ($cursor->lte($today) && $days < 60) {
            if (! $cursor->isWeekend()) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
