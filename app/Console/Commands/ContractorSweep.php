<?php

namespace App\Console\Commands;

use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\RepairContractorRequest;
use App\Models\RepairJobOffer;
use App\Services\Contractors\ContractorDocumentStore;
use App\Services\Contractors\ContractorMail;
use App\Services\Contractors\RepairJobOfferService;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** Hourly housekeeping for the contractor marketplace. Every step is idempotent. */
class ContractorSweep extends Command
{
    protected $signature = 'contractors:sweep';

    protected $description = 'Expiry reminders, auto-pause, invite and offer expiry, digests, reminders and retention for contractors';

    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly ContractorMail $mail,
        private readonly ContractorDocumentStore $store,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach (['expiryReminders', 'pauseLapsed', 'expireDeadlines', 'digests', 'unansweredReminders', 'draftReminders', 'purgeRetention', 'slaAlert'] as $step) {
            try {
                $this->{$step}();
            } catch (\Throwable $exception) {
                Log::error('contractors:sweep '.$step.' failed', ['error' => $exception->getMessage()]);
                $this->error($step.': '.$exception->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function expiryReminders(): void
    {
        $days = collect((array) ContractorSettings::get('reminder_days'))->map(fn ($d) => (int) $d)->filter()->sortDesc()->values();
        if ($days->isEmpty()) {
            return;
        }
        ContractorDocument::query()
            ->with('profile.user', 'profile.documents')
            ->where('status', ContractorDocument::APPROVED)
            ->where('type', 'not like', '%'.ContractorDocumentStore::RENEWAL)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->startOfDay(), now()->addDays($days->first())->endOfDay()])
            ->get()
            ->each(function (ContractorDocument $doc) use ($days) {
                $profile = $doc->profile;
                if (! $profile || $profile->approval_status !== ContractorProfile::APPROVED || $this->store->renewalFor($profile, $doc->type)) {
                    return;
                }
                $left = (int) now()->startOfDay()->diffInDays($doc->expires_at->copy()->startOfDay(), false);
                $stage = $days->filter(fn ($d) => $left <= $d)->min();
                if ($stage === null || ($doc->reminder_stage !== null && (int) $doc->reminder_stage <= $stage)) {
                    return;
                }
                $doc->forceFill(['reminder_stage' => $stage])->save();
                $title = $doc->label().' expires in '.$left.' '.str('day')->plural($left);
                $plain = 'Your '.strtolower($doc->label()).' expires on '.$doc->expires_at->format('j F Y').'. Upload the renewal now so you are not paused. Jobs you already have are not affected.';
                $this->mail->toUser(
                    $profile->user,
                    $title,
                    '<p>'.e($plain).'</p><p><a href="'.e(route('contractor.profile.edit')).'">Upload renewal</a></p>'
                );
                $this->mail->bell($profile->user, $title, $plain, route('contractor.profile.edit'), null, 'contractor.document_expiring');
            });
    }

    private function pauseLapsed(): void
    {
        ContractorProfile::query()
            ->with(['user', 'documents', 'tradeApprovals'])
            ->where('approval_status', ContractorProfile::APPROVED)
            ->get()
            ->each(function (ContractorProfile $profile) {
                foreach ($profile->documents as $doc) {
                    if ($doc->status === ContractorDocument::APPROVED && $doc->isExpired() && ! $doc->expired_notified_at && ! str_ends_with($doc->type, ContractorDocumentStore::RENEWAL)) {
                        $doc->forceFill(['expired_notified_at' => now()])->save();
                        $profile->log('document_expired', null, $doc->type);
                        $title = $doc->label().' has expired';
                        $plain = 'Your '.strtolower($doc->label()).' expired on '.$doc->expires_at->format('j F Y').'. '
                            .(in_array($doc->type, config('contractor_marketplace.base_documents'), true) ? 'You are paused until we approve the renewal.' : 'You cannot take jobs that need it until we approve the renewal.');
                        $this->mail->toUser(
                            $profile->user,
                            $title,
                            '<p>'.e($plain).'</p><p><a href="'.e(route('contractor.profile.edit')).'">Upload renewal</a></p>'
                        );
                        $this->mail->bell($profile->user, $title, $plain, route('contractor.profile.edit'), null, 'contractor.document_expired');
                    }
                }

                $block = ContractorCompliance::profileBlock($profile);
                if ($block && ! $profile->paused_at) {
                    $profile->forceFill(['paused_at' => now(), 'pause_reason' => $block])->save();
                    $profile->log('paused', null, null, $block);
                } elseif (! $block && $profile->paused_at) {
                    $profile->forceFill(['paused_at' => null, 'pause_reason' => null])->save();
                    $profile->log('unpaused', null, null, 'Compliance back in date.');
                    $this->mail->toUser($profile->user, 'You’re active again', '<p>Your documents are back in date. Landlords can send you jobs again.</p>');
                    $this->mail->bell($profile->user, 'You’re active again', 'Your documents are back in date. Landlords can send you jobs again.', route('contractor.jobs.index'), null, 'contractor.profile_active');
                }
                $this->offers->withdrawBlocked($profile);
            });
    }

    private function expireDeadlines(): void
    {
        RepairContractorRequest::query()
            ->with('repairIssue')
            ->whereIn('status', [RepairContractorRequest::SENT, RepairContractorRequest::AWAITING])
            ->whereHas('offers', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereIn('status', RepairJobOffer::AWAITING_INVITE)->where('invite_expires_at', '<', now()))
                ->orWhere(fn ($q) => $q->where('status', RepairJobOffer::OFFER_SENT)->where('offer_expires_at', '<', now()))))
            ->get()
            ->each(fn (RepairContractorRequest $request) => $request->repairIssue && $this->offers->expireStale($request->repairIssue));
    }

    private function digests(): void
    {
        $hours = max(1, (int) ContractorSettings::get('digest_hours'));
        RepairContractorRequest::query()
            ->where('status', RepairContractorRequest::SENT)
            ->where('urgency', '!=', 'emergency')
            ->whereNotNull('first_offer_notified_at')
            ->where(fn ($q) => $q->whereNull('last_digest_at')->orWhere('last_digest_at', '<=', now()->subHours($hours)))
            ->where('first_offer_notified_at', '<=', now()->subHours($hours))
            ->whereHas('offers', fn ($q) => $q->where('status', RepairJobOffer::OFFER_SENT)->whereNull('digested_at'))
            ->get()
            ->each(fn (RepairContractorRequest $request) => $this->offers->sendDigest($request));
    }

    private function unansweredReminders(): void
    {
        RepairJobOffer::query()
            ->with('repairIssue')
            ->where('status', RepairJobOffer::OFFER_SENT)
            ->whereNull('reminded_at')
            ->where('updated_at', '<=', now()->subHours(24))
            ->whereHas('repairIssue', fn ($q) => $q->whereNull('awarded_offer_id'))
            ->get()
            ->each(fn (RepairJobOffer $offer) => $this->offers->remindUnanswered($offer));
    }

    private function draftReminders(): void
    {
        ContractorProfile::query()
            ->with('user')
            ->where('approval_status', ContractorProfile::DRAFT)
            ->whereNotNull('terms_accepted_at')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNull('draft_reminder_stage')->where('created_at', '<=', now()->subDay()))
                ->orWhere(fn ($q) => $q->where('draft_reminder_stage', 1)->where('created_at', '<=', now()->subDays(7))))
            ->get()
            ->each(function (ContractorProfile $profile) {
                $stage = $profile->draft_reminder_stage ? 2 : 1;
                $profile->forceFill(['draft_reminder_stage' => $stage])->save();
                $gaps = ContractorCompliance::submissionGaps($profile);
                $title = $stage === 1 ? 'Finish your contractor sign-up' : 'Still want repair jobs?';
                $plain = 'You are '.(count($gaps) <= 2 ? 'nearly there' : 'part way through').'. '.(count($gaps) ? 'Still needed: '.implode(' ', array_slice($gaps, 0, 3)) : 'Just send it for review.');
                $this->mail->toUser(
                    $profile->user,
                    $title,
                    '<p>'.e($plain).'</p><p><a href="'.e(route('contractor.profile.edit')).'">Carry on</a></p>'
                );
                $this->mail->bell($profile->user, $title, $plain, route('contractor.profile.edit'), null, 'contractor.draft_reminder');
            });
    }

    private function purgeRetention(): void
    {
        $cutoff = now()->subDays(max(30, (int) ContractorSettings::get('document_retention_days')));
        ContractorDocument::query()
            ->whereNotNull('path')
            ->whereHas('profile', fn ($q) => $q
                ->where(fn ($q) => $q->where('approval_status', ContractorProfile::REMOVED)->where('removed_at', '<=', $cutoff))
                ->orWhere(fn ($q) => $q->where('approval_status', ContractorProfile::REJECTED)->where('reviewed_at', '<=', $cutoff)))
            ->get()
            ->each(function (ContractorDocument $doc) {
                $this->store->purge($doc);
                $doc->profile?->log('document_purged', null, $doc->type, 'Retention period ended.');
            });
    }

    private function slaAlert(): void
    {
        $sla = (int) ContractorSettings::get('review_sla_working_days');
        $late = ContractorProfile::query()
            ->where('approval_status', ContractorProfile::SUBMITTED)
            ->whereNotNull('submitted_at')
            ->get()
            ->filter(function (ContractorProfile $profile) use ($sla) {
                $days = 0;
                for ($d = $profile->submitted_at->copy()->startOfDay()->addDay(); $d->lte(now()->startOfDay()) && $days <= $sla; $d->addDay()) {
                    $days += $d->isWeekend() ? 0 : 1;
                }

                return $days > $sla;
            });
        if ($late->isNotEmpty()) {
            $this->mail->toAdmins(
                $late->count().' contractor '.str('application')->plural($late->count()).' over the review SLA',
                '<p>'.$late->count().' waiting more than '.$sla.' working days.</p><p><a href="'.e(route('admin.registrations.contractors.index')).'">Open the review queue</a></p>',
                'contractor-sla-'.now()->toDateString()
            );
        }
    }
}
