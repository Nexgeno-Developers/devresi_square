<?php

namespace App\Services\Contractors;

use App\Enums\CrmNotificationEvent;
use App\Models\ContractorProfile;
use App\Models\RepairContractorRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\RepairJobOfferEvent;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class RepairJobOfferService
{
    public function __construct(
        private readonly CrmNotificationService $notifications,
        private readonly ContractorMail $mail,
        private readonly ContractorSms $sms,
    ) {
    }

    // ── Expiry ──────────────────────────────────────────────────────────────

    public function expireStale(RepairIssue $repair): void
    {
        $expired = [];
        $repair->jobOffers()->whereIn('status', RepairJobOffer::AWAITING_INVITE)
            ->whereNotNull('invite_expires_at')
            ->where('invite_expires_at', '<', now())
            ->with('contractor.contractorProfile')
            ->get()
            ->each(function (RepairJobOffer $offer) use (&$expired) {
                $this->transition($offer, RepairJobOffer::EXPIRED, null, 'No response before the deadline.');
                $expired[] = $offer;
            });

        $repair->jobOffers()->where('status', RepairJobOffer::OFFER_SENT)
            ->whereNotNull('offer_expires_at')
            ->where('offer_expires_at', '<', now())
            ->with('contractor')
            ->get()
            ->each(function (RepairJobOffer $offer) {
                $this->transition($offer, RepairJobOffer::OFFER_EXPIRED, null, 'Offer validity ended.');
                $this->notifyOfferExpired($offer);
            });

        if ($expired !== []) {
            $names = collect($expired)->map(fn (RepairJobOffer $offer) => $offer->contractor?->contractorProfile?->displayName() ?: 'A contractor')->implode(', ');
            $this->notifyLandlord(
                $expired[0],
                CrmNotificationEvent::RepairJobInvitesExpired,
                'Contractors did not respond',
                'These contractors did not respond before the deadline: '.$names.'. You can send the repair to others.',
                'invites-expired-'.collect($expired)->pluck('id')->implode('-')
            );
        }
    }

    // ── Landlord sends ──────────────────────────────────────────────────────

    /**
     * @param  array<int, int>  $contractorIds
     * @param  array<string, mixed>  $options
     * @return array{sent: array<int, string>, skipped: array<int, string>}
     */
    public function sendRequest(RepairIssue $repair, array $contractorIds, User $landlord, array $options = []): array
    {
        $this->assertAssignable($repair);
        $this->expireStale($repair);

        $urgency = array_key_exists($options['urgency'] ?? '', RepairContractorRequest::URGENCIES) ? $options['urgency'] : 'routine';
        $respondBy = ! empty($options['respond_by']) ? Carbon::parse($options['respond_by']) : now()->addHours(ContractorSettings::inviteHours($urgency));
        if ($respondBy->isPast()) {
            throw new ContractorMarketplaceException('The respond-by time must be in the future.');
        }
        $trade = $options['trade'] ?? (ContractorCompliance::requiredTrade($repair) ?: ContractorCompliance::suggestedTrade($repair));

        $request = RepairContractorRequest::query()->firstOrNew(['repair_issue_id' => $repair->id]);
        $openIds = $request->exists
            ? $request->offers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->pluck('contractor_user_id')->all()
            : [];
        $ids = array_values(array_diff(array_unique(array_map('intval', $contractorIds)), $openIds));
        if ($urgency === 'emergency') {
            $emergencyCap = ContractorSettings::maxPerEmergencySend();
            if (count($ids) > $emergencyCap) {
                throw new ContractorMarketplaceException('An emergency can go to '.$emergencyCap.' contractors at most.');
            }
        } else {
            $cap = ContractorSettings::maxPerSend();
            if (count($ids) + count($openIds) > $cap) {
                throw new ContractorMarketplaceException('Send to '.$cap.' contractors at most. Fewer contractors means more of them reply.');
            }
        }

        $request->fill([
            'account_id' => $repair->account_id ?: current_account_id(),
            'urgency' => $urgency,
            'trade' => $options['trade'] ?? $trade,
            'respond_by' => $respondBy,
            'budget_ceiling' => $options['budget_ceiling'] ?? $request->budget_ceiling,
            'show_budget' => (bool) ($options['show_budget'] ?? $request->show_budget),
            'access_notes' => $options['access_notes'] ?? $request->access_notes ?? $repair->access_details,
            'availability_to_arrange' => ! $repair->tenant_availability,
            'status' => RepairContractorRequest::SENT,
            'sent_by' => $request->sent_by ?: $landlord->id,
            'first_sent_at' => $request->first_sent_at ?: now(),
            'last_sent_at' => now(),
            'cancelled_at' => null,
            'cancel_reason' => null,
        ])->save();

        $sent = [];
        $skipped = [];
        foreach ($ids as $contractorId) {
            $user = User::with(['contractorProfile.documents', 'contractorProfile.tradeApprovals'])->find($contractorId);
            $profile = $user?->contractorProfile;
            if (! $profile) {
                $skipped[] = 'Contractor #'.$contractorId.' is not on the approved list.';
                continue;
            }
            $decision = app(ContractorEligibility::class)->assess($profile, $repair, $trade);
            if (! $decision['ok']) {
                $skipped[] = $profile->displayName().': '.$decision['reason'];
                continue;
            }
            $offer = RepairJobOffer::query()->firstOrNew(['repair_issue_id' => $repair->id, 'contractor_user_id' => $user->id]);
            if ($offer->exists && in_array($offer->status, RepairJobOffer::BLOCKING_STATUSES, true)) {
                $skipped[] = $profile->displayName().' already has this repair.';
                continue;
            }
            $from = $offer->exists ? $offer->status : null;
            $offer->fill($this->blankOffer() + [
                'account_id' => $request->account_id,
                'repair_contractor_request_id' => $request->id,
                'invited_by' => $landlord->id,
                'status' => RepairJobOffer::INVITED,
                'availability_snapshot' => $repair->tenant_availability,
                'invited_at' => now(),
                'invite_expires_at' => $respondBy,
            ]);
            $offer->save();
            $this->log($offer, $from, RepairJobOffer::INVITED, $landlord, 'Sent as '.$urgency.'.');
            $this->notify(
                CrmNotificationEvent::RepairJobInvited,
                $offer,
                [$user],
                $urgency === 'emergency' ? 'EMERGENCY repair job' : 'New repair job',
                ($urgency === 'emergency' ? 'Emergency. ' : '').'A landlord sent you a repair in '.$this->area($repair).'. Respond by '.$respondBy->format('D j M, H:i').'. Open your jobs to make an offer or decline.',
                'invite-'.$offer->id.'-'.$offer->invited_at->timestamp,
                route('contractor.jobs.show', $offer)
            );
            if ($urgency === 'emergency') {
                $this->sms->send($user, 'EMERGENCY repair in '.$this->area($repair).'. Respond by '.$respondBy->format('H:i').': '.route('contractor.jobs.show', $offer));
            }
            $sent[] = $profile->displayName();
        }

        if ($sent !== []) {
            $this->notify(
                CrmNotificationEvent::RepairJobRequestSent,
                $request->offers()->latest('id')->first(),
                [$landlord],
                'Sent to contractors',
                'Sent to '.implode(', ', $sent).'. They have until '.$respondBy->format('D j M, H:i').' to respond.',
                'request-sent-'.$request->id.'-'.now()->timestamp
            );
        } elseif (! $request->offers()->exists()) {
            $request->delete();
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /** The landlord edited the repair after sending it. */
    public function detailsChanged(RepairIssue $repair): void
    {
        $request = $repair->contractorRequest;
        if (! $request || ! in_array($request->status, [RepairContractorRequest::SENT, RepairContractorRequest::AWAITING], true)) {
            return;
        }
        $request->forceFill([
            'details_changed_at' => now(),
            'availability_to_arrange' => ! $repair->tenant_availability,
        ])->save();
        $stamp = now()->timestamp;
        foreach ($repair->jobOffers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->with('contractor')->get() as $offer) {
            if ($offer->status === RepairJobOffer::OFFER_SENT) {
                $offer->forceFill(['details_changed_at' => now()])->save();
                $this->log($offer, $offer->status, $offer->status, null, 'Repair details changed after the offer.');
            }
            $this->notify(
                CrmNotificationEvent::RepairJobDetailsChanged,
                $offer,
                [$offer->contractor],
                'Repair details changed',
                $offer->status === RepairJobOffer::OFFER_SENT
                    ? 'The landlord changed the repair details. Check them and update your offer if the price changes.'
                    : 'The landlord changed the repair details. Check them before you make an offer.',
                'details-'.$offer->id.'-'.$stamp,
                route('contractor.jobs.show', $offer)
            );
        }
    }

    /**
     * Landlord rejects every offer and closes the request, or cancels the request outright.
     */
    public function closeRequest(RepairIssue $repair, User $landlord, string $outcome, ?string $reason = null): void
    {
        $request = $repair->contractorRequest;
        if (! $request) {
            throw new ContractorMarketplaceException('This repair has not been sent to contractors.');
        }
        if ($repair->awarded_offer_id) {
            throw new ContractorMarketplaceException('A contractor is already instructed. Cancel that job first.');
        }
        $cancelled = $outcome === RepairContractorRequest::CANCELLED;
        foreach ($repair->jobOffers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->with('contractor')->get() as $offer) {
            $next = $offer->isAwaitingResponse() ? RepairJobOffer::WITHDRAWN : RepairJobOffer::NOT_SELECTED;
            $this->transition($offer, $next, $landlord, $cancelled ? 'Landlord cancelled the request.' : 'Landlord closed the request.');
            $this->notify(
                $cancelled ? CrmNotificationEvent::RepairJobCancelled : CrmNotificationEvent::RepairJobNotSelected,
                $offer,
                [$offer->contractor],
                $cancelled ? 'Repair request cancelled' : 'Repair request closed',
                $cancelled ? 'The landlord cancelled this repair request.' : 'The landlord closed this request without choosing an offer. Thanks for responding.',
                'request-closed-'.$offer->id.'-'.now()->timestamp
            );
        }
        $request->forceFill([
            'status' => $outcome,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ])->save();
    }

    // ── Contractor responds ─────────────────────────────────────────────────

    public function markViewed(RepairJobOffer $offer): void
    {
        if ($offer->status === RepairJobOffer::INVITED) {
            $offer->viewed_at = now();
            $this->transition($offer, RepairJobOffer::VIEWED, $offer->contractor, 'Contractor opened the invitation.');
        }
    }

    public function decline(RepairJobOffer $offer, User $contractor, ?string $code, ?string $reason): void
    {
        $this->assertContractor($offer, $contractor);
        $this->expireOne($offer);
        if (! $offer->isAwaitingResponse()) {
            throw new ContractorMarketplaceException('This invitation can no longer be declined.');
        }
        $label = config('contractor_marketplace.decline_reasons.'.$code);
        $offer->decline_code = $label ? $code : null;
        $offer->decline_reason = $reason ?: $label;
        $this->transition($offer, RepairJobOffer::DECLINED, $contractor, $offer->decline_reason ?: 'Contractor declined.');
        $this->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobInviteDeclined,
            'A contractor declined',
            $this->name($offer).' declined.'.($offer->decline_reason ? ' Reason: '.$offer->decline_reason.'.' : ''),
            'declined-'.$offer->id.'-'.$offer->updated_at?->timestamp
        );
    }

    public function withdrawInvite(RepairJobOffer $offer, User $landlord): void
    {
        $this->assertLandlord($offer, $landlord);
        if (! $offer->isAwaitingResponse()) {
            throw new ContractorMarketplaceException('Only an open invitation can be withdrawn.');
        }
        $this->transition($offer, RepairJobOffer::WITHDRAWN, $landlord, 'Landlord withdrew the invitation.');
        $this->notify(
            CrmNotificationEvent::RepairJobInviteDeclined,
            $offer,
            [$offer->contractor],
            'Repair invitation withdrawn',
            'The landlord withdrew this invitation.',
            'withdrawn-invite-'.$offer->id.'-'.now()->timestamp
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function submitOffer(RepairJobOffer $offer, User $contractor, array $input): void
    {
        $this->assertContractor($offer, $contractor);
        $this->expireOne($offer);
        $offer->loadMissing('repairIssue', 'request');
        $profile = $contractor->contractorProfile;
        if (! $profile) {
            throw new ContractorMarketplaceException('Complete your contractor profile first.');
        }
        if ($reason = ContractorCompliance::blockReason($profile, ContractorCompliance::requiredTrade($offer->repairIssue))) {
            $this->flagCompliance($offer, $reason);
            throw new ContractorMarketplaceException($reason);
        }
        if (! $offer->canQuote()) {
            throw new ContractorMarketplaceException('This job is not open for an offer.');
        }
        if ($offer->repairIssue->awarded_offer_id) {
            throw new ContractorMarketplaceException('This repair has already been awarded.');
        }
        if ($offer->status === RepairJobOffer::INSPECTION_ACCEPTED && ($input['pricing_type'] ?? null) === 'inspection') {
            throw new ContractorMarketplaceException('You have inspected. Send a firm price now.');
        }

        $priced = $this->price($profile, $input);
        $slots = $this->slots($offer, $input);
        $urgency = $offer->request?->urgency ?: 'routine';
        $hours = (int) ($input['valid_hours'] ?? ContractorSettings::offerValidHours($urgency));
        $hours = in_array($hours, [24, 48, 72, 168], true) ? $hours : ContractorSettings::offerValidHours($urgency);

        $from = $offer->status;
        $wasSent = $from === RepairJobOffer::OFFER_SENT;
        $offer->fill($priced + [
            'visit_slots' => $slots,
            'status' => RepairJobOffer::OFFER_SENT,
            'offer_sent_at' => $offer->offer_sent_at ?: now(),
            'offer_expires_at' => now()->addHours($hours),
            'decline_reason' => null,
            'details_changed_at' => null,
            'reconfirm_requested_at' => null,
            'digested_at' => null,
            'reminded_at' => null,
        ]);
        $offer->revision = $wasSent ? $offer->revision + 1 : $offer->revision;
        $offer->save();
        $this->log($offer, $from, RepairJobOffer::OFFER_SENT, $contractor, $wasSent ? 'Offer updated (version '.$offer->revision.').' : ($from === RepairJobOffer::INSPECTION_ACCEPTED ? 'Firm quote after inspection.' : 'Offer sent.'), $priced);

        $request = $offer->request;
        $request?->forceFill(['first_offer_at' => $request->first_offer_at ?: now()])->save();
        $subject = $wasSent ? 'An offer was updated' : ($from === RepairJobOffer::INSPECTION_ACCEPTED ? 'Firm quote after inspection' : 'You have an offer');
        $notice = $this->name($offer).($wasSent ? ' updated their offer: ' : ' sent an offer: ').$this->priceSummary($offer).'.';
        if ($request?->isEmergency() && ! $request->first_offer_notified_at) {
            $this->sms->send($offer->invitedBy, 'Emergency repair: '.$this->name($offer).' offered '.$this->priceSummary($offer).'. Accept: '.route('admin.property_repairs.show', $offer->repair_issue_id));
        }
        $request?->forceFill(['first_offer_notified_at' => $request->first_offer_notified_at ?: now()])->save();
        $offer->forceFill(['digested_at' => now()])->save();
        $this->notifyLandlord($offer, CrmNotificationEvent::RepairJobOfferSent, $subject, $notice, 'offer-'.$offer->id.'-rev-'.$offer->revision.'-'.$offer->updated_at->timestamp);
    }

    public function withdrawOffer(RepairJobOffer $offer, User $contractor, ?string $reason): void
    {
        $this->assertContractor($offer, $contractor);
        if (! in_array($offer->status, [RepairJobOffer::OFFER_SENT, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
            throw new ContractorMarketplaceException('Only a live offer can be withdrawn.');
        }
        DB::transaction(function () use ($offer, $contractor, $reason) {
            $locked = RepairJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [RepairJobOffer::OFFER_SENT, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
                throw new ContractorMarketplaceException('This offer has already been decided.');
            }
            $locked->withdraw_reason = $reason;
            $locked->revision++;
            $this->transition($locked, RepairJobOffer::OFFER_WITHDRAWN, $contractor, $reason ?: 'Contractor withdrew the offer.');
            $offer->setRawAttributes($locked->getAttributes(), true);
        });
        $this->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobOfferWithdrawn,
            'An offer was withdrawn',
            $this->name($offer).' withdrew their offer.'.($reason ? ' Reason: '.$reason.'.' : ''),
            'offer-withdrawn-'.$offer->id.'-'.$offer->revision
        );
    }

    public function requestReconfirm(RepairJobOffer $offer, User $landlord): void
    {
        $this->assertLandlord($offer, $landlord);
        if ($offer->status !== RepairJobOffer::OFFER_EXPIRED) {
            throw new ContractorMarketplaceException('Only an expired offer can be reconfirmed.');
        }
        if ($offer->repairIssue?->awarded_offer_id) {
            throw new ContractorMarketplaceException('This repair has already been awarded.');
        }
        $offer->forceFill(['reconfirm_requested_at' => now()])->save();
        $this->log($offer, $offer->status, $offer->status, $landlord, 'Landlord asked the contractor to reconfirm.');
        $this->notify(
            CrmNotificationEvent::RepairJobReconfirm,
            $offer,
            [$offer->contractor],
            'The landlord wants your expired offer',
            'Your offer expired, but the landlord would like to accept it. Tap Reconfirm to keep the same price.',
            'reconfirm-'.$offer->id.'-'.now()->timestamp,
            route('contractor.jobs.show', $offer)
        );
    }

    public function reconfirm(RepairJobOffer $offer, User $contractor): void
    {
        $this->assertContractor($offer, $contractor);
        if ($offer->status !== RepairJobOffer::OFFER_EXPIRED || ! $offer->reconfirm_requested_at) {
            throw new ContractorMarketplaceException('There is nothing to reconfirm.');
        }
        $offer->loadMissing('repairIssue', 'request');
        if ($offer->repairIssue->awarded_offer_id) {
            throw new ContractorMarketplaceException('This repair has already been awarded.');
        }
        if ($reason = ContractorCompliance::blockReason($contractor->contractorProfile, ContractorCompliance::requiredTrade($offer->repairIssue))) {
            throw new ContractorMarketplaceException($reason);
        }
        $offer->offer_expires_at = now()->addHours(ContractorSettings::offerValidHours($offer->request?->urgency ?: 'routine'));
        $offer->reconfirm_requested_at = null;
        $this->transition($offer, RepairJobOffer::OFFER_SENT, $contractor, 'Contractor reconfirmed the same price.');
        $this->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobOfferSent,
            'Offer reconfirmed',
            $this->name($offer).' reconfirmed their offer: '.$this->priceSummary($offer).'. You can accept it now.',
            'reconfirmed-'.$offer->id.'-'.now()->timestamp
        );
    }

    // ── Landlord decides ────────────────────────────────────────────────────

    public function accept(RepairJobOffer $offer, User $landlord, ?int $expectedRevision = null): void
    {
        $this->assertLandlord($offer, $landlord);
        $winner = null;
        $losers = [];
        $inspection = false;
        $this->expireOne($offer);

        DB::transaction(function () use ($offer, $landlord, $expectedRevision, &$winner, &$losers, &$inspection) {
            $repair = RepairIssue::query()->whereKey($offer->repair_issue_id)->lockForUpdate()->firstOrFail();
            if ($repair->awarded_offer_id) {
                throw new ContractorMarketplaceException('This repair has already been awarded.');
            }
            $locked = RepairJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            $this->expireOne($locked);
            match ($locked->status) {
                RepairJobOffer::OFFER_SENT => null,
                RepairJobOffer::OFFER_WITHDRAWN => throw new ContractorMarketplaceException('This offer was just withdrawn. Please choose another.'),
                RepairJobOffer::OFFER_EXPIRED => throw new ContractorMarketplaceException('This offer has expired. Ask the contractor to reconfirm it.'),
                default => throw new ContractorMarketplaceException('This offer is no longer available to accept.'),
            };
            if ($expectedRevision !== null && (int) $locked->revision !== $expectedRevision) {
                throw new ContractorMarketplaceException('This offer was just updated to '.$this->priceSummary($locked).'. Check the new price, then accept.');
            }
            $profile = $locked->contractor?->contractorProfile;
            $reason = $profile ? ContractorCompliance::blockReason($profile, ContractorCompliance::requiredTrade($repair)) : 'Contractor profile is missing.';
            if ($reason) {
                throw new ContractorMarketplaceException($reason);
            }

            if ($locked->pricing_type === 'inspection') {
                $inspection = true;
                $locked->inspection_accepted_at = now();
                $slot = $locked->earliestSlot();
                if ($repair->tenantChoseAvailability() && $slot && $repair->coversVisit($slot)) {
                    $locked->tenant_confirmed_at = now();
                }
                $this->transition($locked, RepairJobOffer::INSPECTION_ACCEPTED, $landlord, 'Landlord accepted the inspection visit.');
                $losers = $this->closeOtherOffers($repair, $locked, $landlord);
                $winner = $locked->fresh(['contractor.contractorProfile', 'repairIssue.property', 'repairIssue.tenant', 'request']);

                return;
            }

            $slot = $locked->earliestSlot();
            $locked->job_status = $slot ? RepairJobOffer::JOB_SCHEDULED : RepairJobOffer::JOB_ACCEPTED_STATE;
            $locked->scheduled_for = $slot;
            if ($repair->tenantChoseAvailability() && $slot && $repair->coversVisit($slot)) {
                $locked->tenant_confirmed_at = now();
            }
            $this->transition($locked, RepairJobOffer::JOB_ACCEPTED, $landlord, 'Landlord accepted the offer.');
            $repair->awarded_offer_id = $locked->id;
            $repair->sub_status = 'Contractor accepted';
            if (in_array($repair->status, ['Pending', 'Reported'], true)) {
                $repair->status = 'Under Process';
            }
            $repair->dispatched_at = $repair->dispatched_at ?: now();
            RepairIssue::withoutEvents(fn () => $repair->save());
            RepairContractorRequest::query()->where('repair_issue_id', $repair->id)->update([
                'status' => RepairContractorRequest::AWARDED,
                'awarded_at' => now(),
            ]);

            $losers = $this->closeOtherOffers($repair, $locked, $landlord);

            $winner = $locked->fresh(['contractor.contractorProfile', 'repairIssue.property', 'repairIssue.tenant', 'request']);
        });

        if (! $winner) {
            return;
        }
        if ($inspection) {
            $this->announceInspection($winner);
            $this->notify(CrmNotificationEvent::RepairJobNotSelected, $winner, $losers, 'Offer not selected', 'The landlord chose another contractor for the inspection. Your price was not shared with anyone else.', 'inspection-others-'.$winner->id);
        } else {
            $this->announceAward($winner, $losers);
        }
    }

    /**
     * @return array<int, User>
     */
    private function closeOtherOffers(RepairIssue $repair, RepairJobOffer $winner, User $landlord): array
    {
        $losers = [];
        $others = RepairJobOffer::query()
            ->where('repair_issue_id', $repair->id)
            ->where('id', '!=', $winner->id)
            ->whereIn('status', RepairJobOffer::OPEN_STATUSES)
            ->lockForUpdate()
            ->get();
        foreach ($others as $other) {
            $other->loadMissing('contractor');
            $next = $other->isAwaitingResponse() ? RepairJobOffer::WITHDRAWN : RepairJobOffer::NOT_SELECTED;
            $this->transition($other, $next, $landlord, 'The landlord chose another contractor.');
            if ($other->contractor) {
                $losers[] = $other->contractor;
            }
        }

        return $losers;
    }

    public function declineOffer(RepairJobOffer $offer, User $landlord): void
    {
        $this->assertLandlord($offer, $landlord);
        DB::transaction(function () use ($offer, $landlord) {
            $repair = RepairIssue::query()->whereKey($offer->repair_issue_id)->lockForUpdate()->firstOrFail();
            $locked = RepairJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [RepairJobOffer::OFFER_SENT, RepairJobOffer::OFFER_EXPIRED], true)) {
                throw new ContractorMarketplaceException('This offer can no longer be declined.');
            }
            $this->transition($locked, RepairJobOffer::NOT_SELECTED, $landlord, 'Landlord declined this offer.');
            if ((int) $repair->awarded_offer_id === (int) $locked->id) {
                RepairIssue::withoutEvents(fn () => $repair->forceFill(['awarded_offer_id' => null])->save());
            }
            $offer->setRawAttributes($locked->getAttributes(), true);
        });
        $this->notify(
            CrmNotificationEvent::RepairJobNotSelected,
            $offer,
            [$offer->contractor],
            'Offer not selected',
            'The landlord decided not to go ahead with your offer. Thanks for responding.',
            'declined-offer-'.$offer->id
        );
    }

    public function cancel(RepairJobOffer $offer, User $actor, string $side, ?string $reason = null): void
    {
        $side === 'contractor' ? $this->assertContractor($offer, $actor) : $this->assertLandlord($offer, $actor);
        DB::transaction(function () use ($offer, $actor, $side, $reason) {
            $repair = RepairIssue::query()->whereKey($offer->repair_issue_id)->lockForUpdate()->firstOrFail();
            $locked = RepairJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
                throw new ContractorMarketplaceException('Only an accepted job can be cancelled.');
            }
            if ($locked->isFinished()) {
                throw new ContractorMarketplaceException('This job is already complete. Report a problem instead.');
            }
            $locked->cancel_reason = $reason;
            $this->transition($locked, RepairJobOffer::CANCELLED, $actor, ($side === 'contractor' ? 'Contractor cancelled after award.' : 'Landlord cancelled after award.').($reason ? ' '.$reason : ''));
            if ((int) $repair->awarded_offer_id === (int) $locked->id) {
                RepairIssue::withoutEvents(fn () => $repair->forceFill(['awarded_offer_id' => null, 'sub_status' => 'Awaiting contractor'])->save());
            }
            RepairContractorRequest::query()->where('repair_issue_id', $repair->id)->update([
                'status' => RepairContractorRequest::AWAITING,
                'awarded_at' => null,
            ]);
            $offer->setRawAttributes($locked->getAttributes(), true);
        });

        $offer->loadMissing('contractor', 'invitedBy');
        if ($side === 'contractor') {
            $this->notifyLandlord(
                $offer,
                CrmNotificationEvent::RepairJobCancelled,
                'Your contractor cancelled',
                $this->name($offer).' cancelled the job'.($reason ? ': '.$reason : '').'. The repair is awaiting a contractor. You can send it to the others again.',
                'cancelled-by-contractor-'.$offer->id.'-'.now()->timestamp
            );
        } else {
            $this->notify(
                CrmNotificationEvent::RepairJobCancelled,
                $offer,
                [$offer->contractor],
                'Job cancelled by the landlord',
                'The landlord cancelled this job'.($reason ? ': '.$reason : '').'. If you had already attended, raise a call-out charge with the landlord directly.',
                'cancelled-by-landlord-'.$offer->id.'-'.now()->timestamp
            );
        }
    }

    // ── After award ─────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     */
    public function progress(RepairJobOffer $offer, User $contractor, string $step, array $data = []): void
    {
        $this->assertContractor($offer, $contractor);
        if ($offer->status !== RepairJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('This job is not active.');
        }
        $offer->loadMissing('repairIssue.tenant', 'invitedBy');
        switch ($step) {
            case 'schedule':
                $when = Carbon::parse($data['scheduled_for'] ?? '');
                if ($when->isPast()) {
                    throw new ContractorMarketplaceException('Pick a visit time in the future.');
                }
                $matches = $offer->repairIssue && $offer->repairIssue->tenantChoseAvailability() && $offer->repairIssue->coversVisit($when);
                $offer->forceFill([
                    'job_status' => RepairJobOffer::JOB_SCHEDULED,
                    'scheduled_for' => $when,
                    'tenant_confirmed_at' => $matches ? ($offer->tenant_confirmed_at ?: now()) : null,
                ])->save();
                $this->log($offer, $offer->status, $offer->status, $contractor, $matches
                    ? 'Visit booked for '.$when->format('j M Y H:i').', using the tenant’s time.'
                    : 'Visit proposed for '.$when->format('j M Y H:i').'. Waiting for the tenant to confirm.');
                $notice = $this->name($offer).($matches ? ' booked' : ' proposed').' the visit for '.$when->format('D j M, H:i').'.';
                if (! $matches) {
                    $notice .= ' The tenant needs to confirm it.';
                }
                $this->notifyLandlord($offer, CrmNotificationEvent::RepairJobProgress, $matches ? 'Visit booked' : 'Visit waiting on the tenant', $notice, 'scheduled-'.$offer->id.'-'.$when->timestamp);
                $this->notifyTenantVisit($offer, $matches ? 'Repair visit booked' : 'Please confirm a repair visit');
                break;
            case 'start':
                $this->assertVisitSettled($offer);
                $offer->forceFill(['job_status' => RepairJobOffer::JOB_IN_PROGRESS, 'started_at' => now()])->save();
                $this->log($offer, $offer->status, $offer->status, $contractor, 'Work started.');
                $this->notifyLandlord($offer, CrmNotificationEvent::RepairJobProgress, 'Work started', $this->name($offer).' has started the repair.', 'started-'.$offer->id);
                $this->notify(CrmNotificationEvent::RepairJobProgress, $offer, [$offer->repairIssue?->tenant], 'Work has started on your repair', 'The contractor has started the repair.', 'started-tenant-'.$offer->id);
                break;
            case 'complete':
                if ($offer->job_status !== RepairJobOffer::JOB_IN_PROGRESS) {
                    $this->assertVisitSettled($offer);
                }
                $offer->forceFill([
                    'job_status' => RepairJobOffer::JOB_COMPLETED,
                    'completed_at' => now(),
                    'completion_note' => $data['completion_note'] ?? null,
                ])->save();
                RepairContractorRequest::query()->where('repair_issue_id', $offer->repair_issue_id)->update(['completed_at' => now()]);
                $this->log($offer, $offer->status, $offer->status, $contractor, 'Marked complete.'.(! empty($data['completion_note']) ? ' '.$data['completion_note'] : ''));
                $repair = $offer->repairIssue;
                $address = $repair?->property?->full_address ?: ($repair ? $this->area($repair) : 'the property');
                $issue = trim((string) ($repair?->description ?: $repair?->reference_number ?: 'the repair'));
                $this->notifyLandlord(
                    $offer,
                    CrmNotificationEvent::RepairJobCompleted,
                    'Repair completed',
                    'The repair ticket for '.$issue.' at '.$address.' has been marked as Completed by '.$this->name($offer).'. The job outcome, visit history, and contractor invoice have been attached directly to the property record.',
                    'completed-'.$offer->id
                );
                $this->notify(CrmNotificationEvent::RepairJobCompleted, $offer, [$offer->repairIssue?->tenant], 'Repair completed', 'The contractor has marked your repair complete. Tell your landlord if anything is not fixed.', 'completed-tenant-'.$offer->id);
                break;
            default:
                throw new ContractorMarketplaceException('Unknown step.');
        }
    }

    public function confirmComplete(RepairJobOffer $offer, User $landlord): void
    {
        $this->assertLandlord($offer, $landlord);
        if ($offer->status !== RepairJobOffer::JOB_ACCEPTED || $offer->job_status !== RepairJobOffer::JOB_COMPLETED) {
            throw new ContractorMarketplaceException('The contractor has not marked this job complete yet.');
        }
        $offer->forceFill(['job_status' => RepairJobOffer::JOB_CONFIRMED, 'confirmed_at' => now()])->save();
        RepairContractorRequest::query()->where('repair_issue_id', $offer->repair_issue_id)->update(['status' => RepairContractorRequest::COMPLETED]);
        $repair = $offer->repairIssue;
        if ($repair && in_array((string) $repair->status, ['Pending', 'Reported', 'Under Process', 'Completed'], true)) {
            RepairIssue::withoutEvents(fn () => $repair->forceFill([
                'status' => 'Work Completed',
                'sub_status' => 'Work Completed',
                'resolved_at' => $repair->resolved_at ?: now(),
                'resolved_by' => $repair->resolved_by ?: $landlord->id,
            ])->save());
        }
        $this->log($offer, $offer->status, $offer->status, $landlord, 'Landlord confirmed completion.');
        $this->notify(CrmNotificationEvent::RepairJobCompleted, $offer, [$offer->contractor], 'Job confirmed complete', 'The landlord confirmed the job is complete.', 'confirmed-'.$offer->id);
    }

    public function dispute(RepairJobOffer $offer, User $actor, string $side, string $reason): void
    {
        $side === 'contractor' ? $this->assertContractor($offer, $actor) : $this->assertLandlord($offer, $actor);
        if ($offer->status !== RepairJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('Only an awarded job can be disputed.');
        }
        $offer->forceFill(['disputed_at' => now(), 'dispute_reason' => $reason])->save();
        $this->log($offer, $offer->status, $offer->status, $actor, 'Dispute raised by '.$side.': '.$reason);
        $this->mail->toAdmins(
            'Disputed repair job',
            '<p>'.e(ucfirst($side)).' raised a dispute on repair '.e($offer->repairIssue?->reference_number ?: '#'.$offer->repair_issue_id).': '.e($reason).'</p><p><a href="'.e(route('admin.registrations.contractors.jobs')).'">Open stuck and disputed jobs</a></p>'
        );
        $other = $side === 'contractor' ? $offer->invitedBy : $offer->contractor;
        $this->notify(CrmNotificationEvent::RepairJobDisputed, $offer, [$other], 'Job flagged as disputed', 'This job was flagged as disputed. Our team will be in touch.', 'disputed-'.$offer->id.'-'.now()->timestamp);
    }

    public function tenantConfirm(RepairJobOffer $offer): void
    {
        if (! in_array($offer->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
            throw new ContractorMarketplaceException('This visit is no longer booked.');
        }
        if ($offer->tenant_confirmed_at) {
            return;
        }
        $offer->forceFill(['tenant_confirmed_at' => now()])->save();
        $this->log($offer, $offer->status, $offer->status, $offer->repairIssue?->tenant, 'Tenant confirmed the visit time.');
        $this->notify(CrmNotificationEvent::RepairJobProgress, $offer, [$offer->contractor, $offer->invitedBy], 'Tenant confirmed the visit', 'The tenant confirmed the visit time.', 'tenant-confirmed-'.$offer->id);
    }

    /** Super Admin unsticks a job; every override is in the audit trail. */
    public function override(RepairJobOffer $offer, User $admin, string $action, string $note): void
    {
        abort_unless($admin->isSuperAdmin(), 403);
        $offer->loadMissing('contractor', 'invitedBy');
        switch ($action) {
            case 'resolve_dispute':
                $wasDisputed = (bool) $offer->disputed_at;
                $offer->forceFill(['disputed_at' => null])->save();
                $this->log($offer, $offer->status, $offer->status, $admin, 'Admin resolved dispute: '.$note);
                if ($wasDisputed) {
                    $this->tellBoth($offer, CrmNotificationEvent::RepairJobDisputed, 'Dispute resolved', 'An admin resolved the dispute: '.$note, 'dispute-resolved-'.$offer->id);
                }
                break;
            case 'close':
                $wasOpen = in_array($offer->status, RepairJobOffer::OPEN_STATUSES, true);
                $this->transition($offer, $wasOpen ? RepairJobOffer::CLOSED : $offer->status, $admin, 'Admin closed: '.$note);
                if ($wasOpen) {
                    $this->tellBoth($offer, CrmNotificationEvent::RepairJobCancelled, 'Job closed', 'An admin closed this job: '.$note, 'admin-closed-'.$offer->id);
                }
                break;
            case 'cancel_award':
                if ($offer->status === RepairJobOffer::JOB_ACCEPTED) {
                    DB::transaction(function () use ($offer, $admin, $note) {
                        $this->transition($offer, RepairJobOffer::CANCELLED, $admin, 'Admin cancelled the award: '.$note);
                        RepairIssue::withoutEvents(fn () => RepairIssue::query()->whereKey($offer->repair_issue_id)->where('awarded_offer_id', $offer->id)->update(['awarded_offer_id' => null]));
                        RepairContractorRequest::query()->where('repair_issue_id', $offer->repair_issue_id)->update(['status' => RepairContractorRequest::AWAITING]);
                    });
                    $this->tellBoth($offer, CrmNotificationEvent::RepairJobCancelled, 'Award cancelled', 'An admin cancelled this job: '.$note, 'admin-cancel-award-'.$offer->id);
                }
                break;
            default:
                throw new ContractorMarketplaceException('Unknown action.');
        }
    }

    private function tellBoth(RepairJobOffer $offer, CrmNotificationEvent $event, string $subject, string $notice, string $milestone): void
    {
        $this->notify($event, $offer, [$offer->contractor], $subject, $notice, $milestone.'-contractor', route('contractor.jobs.show', $offer));
        $this->notifyLandlord($offer, $event, $subject, $notice, $milestone.'-landlord');
    }

    // ── Compliance side effects ─────────────────────────────────────────────

    public function closeForRepair(RepairIssue $repair): void
    {
        $offers = RepairJobOffer::query()
            ->where('repair_issue_id', $repair->id)
            ->whereIn('status', array_merge(RepairJobOffer::OPEN_STATUSES, [RepairJobOffer::JOB_ACCEPTED]))
            ->with('contractor')
            ->get();
        foreach ($offers as $offer) {
            if ($offer->status === RepairJobOffer::JOB_ACCEPTED) {
                if ($offer->isFinished()) {
                    continue;
                }
                $this->transition($offer, RepairJobOffer::CANCELLED, null, 'Repair was closed.');
                $this->notify(CrmNotificationEvent::RepairJobCancelled, $offer, [$offer->contractor], 'Job cancelled', 'This repair was closed, so the job is cancelled.', 'repair-closed-'.$offer->id, route('contractor.jobs.show', $offer));
            } else {
                $awaiting = $offer->isAwaitingResponse();
                $this->transition($offer, RepairJobOffer::CLOSED, null, 'Repair was closed.');
                $this->notify(
                    CrmNotificationEvent::RepairJobCancelled,
                    $offer,
                    [$offer->contractor],
                    $awaiting ? 'Repair invitation closed' : 'Repair request closed',
                    'This repair was closed, so this '.($awaiting ? 'invitation' : 'offer').' is closed.',
                    'repair-closed-open-'.$offer->id,
                    route('contractor.jobs.show', $offer)
                );
            }
        }
        RepairContractorRequest::query()->where('repair_issue_id', $repair->id)
            ->whereIn('status', [RepairContractorRequest::SENT, RepairContractorRequest::AWAITING])
            ->update(['status' => RepairContractorRequest::CLOSED]);
        if ($repair->awarded_offer_id && ! RepairJobOffer::query()->whereKey($repair->awarded_offer_id)->where('status', RepairJobOffer::JOB_ACCEPTED)->exists()) {
            RepairIssue::withoutEvents(fn () => $repair->forceFill(['awarded_offer_id' => null])->save());
        }
    }

    /**
     * Withdraws open invitations and offers the contractor can no longer honour,
     * and flags awarded jobs without cancelling them.
     */
    public function withdrawBlocked(ContractorProfile $profile, ?string $forcedReason = null): void
    {
        $offers = RepairJobOffer::query()
            ->where('contractor_user_id', $profile->user_id)
            ->whereIn('status', array_merge(RepairJobOffer::OPEN_STATUSES, [RepairJobOffer::JOB_ACCEPTED]))
            ->with('repairIssue')
            ->get();
        foreach ($offers as $offer) {
            $reason = $forcedReason ?: ContractorCompliance::blockReason($profile, $offer->repairIssue ? ContractorCompliance::requiredTrade($offer->repairIssue) : null);
            if (! $reason) {
                continue;
            }
            if ($offer->status === RepairJobOffer::JOB_ACCEPTED) {
                if (! $offer->isFinished()) {
                    $this->flagCompliance($offer, $reason);
                }
                continue;
            }
            $next = $offer->isAwaitingResponse() ? RepairJobOffer::WITHDRAWN : RepairJobOffer::OFFER_WITHDRAWN;
            $offer->withdraw_reason = $reason;
            $this->transition($offer, $next, null, 'Automatically withdrawn: '.$reason);
            $what = $next === RepairJobOffer::WITHDRAWN ? 'invitation' : 'offer';
            $this->notifyLandlord(
                $offer,
                CrmNotificationEvent::RepairJobOfferWithdrawn,
                'A contractor was withdrawn',
                $profile->displayName().' can no longer take this job ('.$reason.'), so their '.$what.' was withdrawn.',
                'auto-withdrawn-'.$offer->id
            );
            $this->notify(
                CrmNotificationEvent::RepairJobOfferWithdrawn,
                $offer,
                [$offer->contractor],
                'This job was withdrawn',
                'Your '.$what.' was withdrawn: '.$reason,
                'auto-withdrawn-contractor-'.$offer->id,
                route('contractor.jobs.show', $offer)
            );
        }
    }

    public function withdrawOpenForSuspended(ContractorProfile $profile): void
    {
        $this->withdrawBlocked($profile, 'Contractor is no longer approved.');
    }

    public function flagCompliance(RepairJobOffer $offer, string $reason): void
    {
        if ($offer->compliance_notified_at || ! in_array($offer->status, array_merge(RepairJobOffer::OPEN_STATUSES, [RepairJobOffer::JOB_ACCEPTED]), true)) {
            return;
        }
        $offer->forceFill(['compliance_notified_at' => now()])->save();
        $this->notify(
            CrmNotificationEvent::RepairContractorComplianceBlocked,
            $offer,
            array_filter([$offer->contractor, $offer->invitedBy]),
            'Contractor insurance or registration needs attention',
            $reason.' The job already accepted is not cancelled automatically.',
            'compliance-'.$offer->id
        );
        if ($offer->status === RepairJobOffer::JOB_ACCEPTED) {
            $this->mail->toAdmins('Awarded job with lapsed compliance', '<p>'.e($this->name($offer)).' has an awarded job on repair '.e($offer->repairIssue?->reference_number ?: '#'.$offer->repair_issue_id).'. '.e($reason).'</p>');
        }
    }

    // ── Digest and reminders (run by the sweep) ─────────────────────────────

    public function sendDigest(RepairContractorRequest $request): void
    {
        $pending = $request->offers()->where('status', RepairJobOffer::OFFER_SENT)->whereNull('digested_at')->with('contractor.contractorProfile')->get();
        if ($pending->isEmpty()) {
            return;
        }
        $lines = $pending->map(fn (RepairJobOffer $offer) => $this->name($offer).': '.$this->priceSummary($offer))->implode('; ');
        $this->notifyLandlord(
            $pending->first(),
            CrmNotificationEvent::RepairJobOfferDigest,
            $pending->count().' new '.($pending->count() === 1 ? 'offer' : 'offers'),
            'New offers on this repair. '.$lines.'.',
            'digest-'.$request->id.'-'.now()->timestamp
        );
        RepairJobOffer::query()->whereIn('id', $pending->pluck('id'))->update(['digested_at' => now()]);
        $request->forceFill(['last_digest_at' => now()])->save();
    }

    public function remindUnanswered(RepairJobOffer $offer): void
    {
        $offer->forceFill(['reminded_at' => now()])->save();
        $this->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobOfferReminder,
            'Offer waiting for you',
            $this->name($offer).'’s offer has been waiting 24 hours. It expires '.optional($offer->offer_expires_at)->format('D j M, H:i').'.',
            'reminder-'.$offer->id.'-rev-'.$offer->revision
        );
    }

    // ── Pricing ─────────────────────────────────────────────────────────────

    public function priceSummary(RepairJobOffer $offer): string
    {
        $vat = (float) $offer->vat_amount > 0 ? ' inc VAT' : ((float) $offer->vat_rate > 0 || $offer->vat_treatment === 'exclusive' ? ' inc VAT' : ' (no VAT)');
        $total = '£'.number_format((float) $offer->gross_amount, 2).$vat;
        $cap = $offer->price_cap ? ', capped at £'.number_format($this->withVat((float) $offer->price_cap, $offer), 2) : '';

        return match ($offer->pricing_type) {
            'hourly' => 'Estimated '.$total.' (£'.number_format((float) $offer->hourly_rate, 2).'/hr × '.$this->hours($offer->estimated_hours).' hrs'.$cap.')',
            'callout_hourly' => 'Estimated '.$total.' (call-out £'.number_format((float) $offer->call_out_fee, 2).' + £'.number_format((float) $offer->hourly_rate, 2).'/hr × '.$this->hours($offer->estimated_hours).' hrs'.$cap.')',
            'inspection' => (float) $offer->gross_amount > 0 ? 'Inspection '.$total.', firm quote after the visit' : 'Free inspection, firm quote after the visit',
            default => ($offer->price_is_estimate ? 'Estimated ' : 'Fixed ').$total,
        }.$this->materialsPhrase($offer);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function price(ContractorProfile $profile, array $input): array
    {
        $type = $input['pricing_type'] ?? null;
        if (! array_key_exists((string) $type, RepairJobOffer::PRICING_TYPES)) {
            throw new ContractorMarketplaceException('Choose how you want to price this job.');
        }
        $registered = (bool) $profile->vat_registered;
        if ($registered && blank($profile->vat_number)) {
            throw new ContractorMarketplaceException('Add your VAT number to your profile first.');
        }
        $rate = $registered ? (float) ($input['vat_rate'] ?? 20) : 0.0;
        if (! in_array($rate, $registered ? [20.0, 5.0, 0.0] : [0.0], true)) {
            throw new ContractorMarketplaceException('VAT rate must be 20%, 5% or 0%.');
        }

        $materials = ($input['materials'] ?? 'excluded') === 'extra' ? 'excluded' : ($input['materials'] ?? 'excluded');
        if (! in_array($materials, ['included', 'excluded', 'estimate'], true)) {
            throw new ContractorMarketplaceException('Say how materials are charged.');
        }
        $materialsEstimate = $materials === 'estimate' ? round((float) ($input['materials_estimate'] ?? 0), 2) : null;
        if ($materials === 'estimate' && $materialsEstimate <= 0) {
            throw new ContractorMarketplaceException('Add your materials estimate.');
        }

        $hourly = round((float) ($input['hourly_rate'] ?? 0), 2);
        $hours = round((float) ($input['estimated_hours'] ?? 0), 2);
        $callOut = round((float) ($input['call_out_fee'] ?? 0), 2);
        $cap = filled($input['price_cap'] ?? null) ? round((float) $input['price_cap'], 2) : null;

        $base = match ($type) {
            'fixed' => round((float) ($input['amount'] ?? 0), 2),
            'hourly' => round($hourly * $hours, 2),
            'callout_hourly' => round($callOut + $hourly * $hours, 2),
            'inspection' => round((float) ($input['inspection_fee'] ?? 0), 2),
        };
        if ($type === 'fixed' && $base <= 0) {
            throw new ContractorMarketplaceException('Enter your price.');
        }
        if (in_array($type, ['hourly', 'callout_hourly'], true) && ($hourly <= 0 || $hours <= 0)) {
            throw new ContractorMarketplaceException('Enter your hourly rate and estimated hours.');
        }
        if ($type === 'callout_hourly' && $callOut <= 0) {
            throw new ContractorMarketplaceException('Enter your call-out fee.');
        }
        if ($base < 0) {
            throw new ContractorMarketplaceException('Prices cannot be negative.');
        }
        if ($cap !== null && $cap < $base) {
            throw new ContractorMarketplaceException('The do-not-exceed cap must be at least your estimate.');
        }

        $net = round($base + (float) $materialsEstimate, 2);
        $vat = round($net * $rate / 100, 2);

        return [
            'pricing_type' => $type,
            'vat_treatment' => $registered ? 'exclusive' : 'not_registered',
            'vat_rate' => $rate,
            'net_amount' => $net,
            'vat_amount' => $vat,
            'gross_amount' => round($net + $vat, 2),
            'hourly_rate' => in_array($type, ['hourly', 'callout_hourly'], true) ? $hourly : null,
            'estimated_hours' => in_array($type, ['hourly', 'callout_hourly'], true) ? $hours : null,
            'call_out_fee' => $type === 'callout_hourly' ? $callOut : null,
            'minimum_hours' => null,
            'price_cap' => in_array($type, ['hourly', 'callout_hourly'], true) ? $cap : null,
            'inspection_fee' => $type === 'inspection' ? $base : null,
            'materials' => $materials,
            'materials_estimate' => $materialsEstimate,
            'price_is_estimate' => in_array($type, ['hourly', 'callout_hourly'], true) || $materials === 'estimate',
            'offer_note' => filled($input['offer_note'] ?? null) ? mb_substr((string) $input['offer_note'], 0, 2000) : null,
        ];
    }

    private function assertVisitSettled(RepairJobOffer $offer): void
    {
        $offer->loadMissing('repairIssue');
        if ($offer->visitIsSettled()) {
            return;
        }
        if (! $offer->agreedVisitAt()) {
            throw new ContractorMarketplaceException('Book a visit time first. The tenant needs to confirm it before you start.');
        }

        throw new ContractorMarketplaceException('The tenant needs to confirm this visit time before you start.');
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private function announceAward(RepairJobOffer $winner, array $losers): void
    {
        $repair = $winner->repairIssue;
        $property = $repair?->property;
        $tenant = $repair?->tenant;
        $summary = $this->priceSummary($winner);
        $address = trim(collect([$property?->line_1, $property?->line_2, $property?->city, $property?->postcode])->filter()->implode(', '));
        $when = $winner->scheduled_for;

        $details = '<p>The landlord accepted your offer: '.e($summary).'.</p>'
            .'<p><strong>Address:</strong> '.e($address ?: 'see the job').'<br>'
            .'<strong>Access:</strong> '.e($winner->request?->access_notes ?: $repair?->access_details ?: 'None recorded').'<br>'
            .'<strong>Tenant:</strong> '.e(trim(($tenant?->name ?: '').' '.($tenant?->phone ?: '').' '.($tenant?->email ?: '')) ?: 'Not listed').'<br>'
            .'<strong>Visit:</strong> '.e($when ? $when->format('D j M Y, H:i') : 'Book a time from the job page').'</p>'
            .'<p><a href="'.e(route('contractor.jobs.show', $winner)).'">Open the job</a></p>';
        $this->mail->toUser($winner->contractor, 'Job accepted: '.($repair?->reference_number ?: 'repair'), $details, $when ? [$this->ics($winner, $when, $address)] : []);
        $this->notify(CrmNotificationEvent::RepairJobAccepted, $winner, [$winner->contractor], 'Your offer was accepted', 'The landlord accepted your offer. '.$summary.'. The full address and tenant contact are now on the job.', 'accepted-winner-'.$winner->id, route('contractor.jobs.show', $winner), ['system']);
        $this->notifyLandlord($winner, CrmNotificationEvent::RepairJobAccepted, 'Job accepted', 'You instructed '.$this->name($winner).'. '.$summary.'.', 'accepted-landlord-'.$winner->id);
        $this->notify(CrmNotificationEvent::RepairJobNotSelected, $winner, $losers, 'Offer not selected', 'The landlord chose another contractor this time. Your price was not shared with anyone else.', 'accepted-others-'.$winner->id);
        $this->notifyTenantVisit($winner, 'A contractor has been booked');
    }

    private function announceInspection(RepairJobOffer $offer): void
    {
        $repair = $offer->repairIssue;
        $property = $repair?->property;
        $address = trim(collect([$property?->line_1, $property?->line_2, $property?->city, $property?->postcode])->filter()->implode(', '));
        $when = $offer->earliestSlot();
        $this->mail->toUser(
            $offer->contractor,
            'Inspection accepted: '.($repair?->reference_number ?: 'repair'),
            '<p>The landlord accepted your inspection visit. Inspect, then send a firm price from the job page.</p><p><strong>Address:</strong> '.e($address).'<br><strong>Access:</strong> '.e($offer->request?->access_notes ?: $repair?->access_details ?: 'None recorded').'</p><p><a href="'.e(route('contractor.jobs.show', $offer)).'">Open the job</a></p>',
            $when ? [$this->ics($offer, $when, $address)] : []
        );
        $this->notify(CrmNotificationEvent::RepairJobAccepted, $offer, [$offer->contractor], 'Inspection accepted', 'The landlord accepted your inspection. The address is now on the job.', 'inspection-'.$offer->id, route('contractor.jobs.show', $offer), ['system']);
        $this->notifyTenantVisit($offer, 'A contractor will inspect the repair');
    }

    public function notifyTenantVisit(RepairJobOffer $offer, string $subject): void
    {
        $tenant = $offer->repairIssue?->tenant;
        if (! $tenant) {
            return;
        }
        $offer->loadMissing('repairIssue');
        $when = $offer->agreedVisitAt();
        $needsConfirm = $when && ! $offer->visitIsSettled();
        $notice = $when
            ? ($needsConfirm
                ? 'A contractor proposed '.$when->format('D j M, H:i').'. Please confirm this time works, or offer another.'
                : 'A contractor will visit on '.$when->format('D j M, H:i').'.')
            : 'A contractor has been booked and will be in touch to arrange a time.';
        $confirmUrl = null;
        if ($needsConfirm) {
            $confirmUrl = URL::temporarySignedRoute('repair_visit.confirm', now()->addDays(7), ['offer' => $offer->id]);
            $notice .= ' Please confirm this time works: '.$confirmUrl;
        }
        $this->notify(CrmNotificationEvent::RepairJobAccepted, $offer, [$tenant], $subject, $notice, 'visit-tenant-'.$offer->id.'-'.($when?->timestamp ?? 0), $confirmUrl ?: route('tenant.maintenance'));
    }

    /**
     * @return array{type: string, data: string, name: string, options: array<string, string>}
     */
    public function ics(RepairJobOffer $offer, Carbon $start, string $address): array
    {
        $esc = fn (string $value) => addcslashes($value, ",;\\\n");
        $ref = $offer->repairIssue?->reference_number ?: 'Repair '.$offer->repair_issue_id;
        $body = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('app.name').'//Contractor jobs//EN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:repair-offer-'.$offer->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$start->copy()->addHours(2)->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$esc('Repair visit: '.$ref),
            'LOCATION:'.$esc($address),
            'DESCRIPTION:'.$esc(mb_substr((string) $offer->repairIssue?->description, 0, 500)),
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";

        return ['type' => 'data', 'data' => $body, 'name' => 'repair-visit.ics', 'options' => ['mime' => 'text/calendar']];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function slots(RepairJobOffer $offer, array $input): array
    {
        $tenant = $offer->repairIssue->tenant_availability;
        $slots = [];
        foreach ((array) ($input['visit_slots'] ?? []) as $slot) {
            if ($tenant && $slot === $tenant->toIso8601String()) {
                $slots[] = $slot;
            }
        }
        if (filled($input['proposed_slot'] ?? null)) {
            $proposed = Carbon::parse($input['proposed_slot']);
            if ($proposed->isPast()) {
                throw new ContractorMarketplaceException('Pick a visit time in the future.');
            }
            $slots[] = $proposed->toIso8601String();
        }
        if ($slots === []) {
            throw new ContractorMarketplaceException($tenant ? 'Tap the tenant’s time or propose another.' : 'The tenant has not given a time. Propose one.');
        }

        return array_values(array_unique($slots));
    }

    private function blankOffer(): array
    {
        return array_fill_keys([
            'pricing_type', 'vat_treatment', 'vat_rate', 'net_amount', 'vat_amount', 'gross_amount', 'hourly_rate',
            'minimum_hours', 'call_out_fee', 'estimated_hours', 'price_cap', 'inspection_fee', 'materials',
            'materials_estimate', 'offer_note', 'decline_reason', 'decline_code', 'withdraw_reason', 'visit_slots',
            'viewed_at', 'offer_sent_at', 'offer_expires_at', 'decided_at', 'compliance_notified_at', 'details_changed_at',
            'inspection_accepted_at', 'reconfirm_requested_at', 'reminded_at', 'digested_at', 'job_status', 'scheduled_for',
            'started_at', 'completed_at', 'completion_note', 'confirmed_at', 'tenant_confirmed_at', 'disputed_at',
            'dispute_reason', 'cancel_reason',
        ], null) + ['price_is_estimate' => false];
    }

    private function materialsPhrase(RepairJobOffer $offer): string
    {
        return match ($offer->materials) {
            'included' => ', materials included',
            'estimate' => ', includes £'.number_format((float) $offer->materials_estimate, 2).' materials estimate',
            'excluded' => ', materials extra',
            default => '',
        };
    }

    private function withVat(float $net, RepairJobOffer $offer): float
    {
        return round($net * (1 + (float) $offer->vat_rate / 100), 2);
    }

    private function hours($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    }

    public function place(RepairIssue $repair, bool $full): string
    {
        if (! $full) {
            return $this->area($repair);
        }
        $property = $repair->property;

        return trim(collect([$property?->line_1, $property?->line_2, $property?->city, $property?->postcode])->filter()->implode(', '));
    }

    private function area(RepairIssue $repair): string
    {
        $property = $repair->property;

        return trim(collect([$property?->city, ContractorCompliance::postcodeDistrict($property?->postcode)])->filter()->implode(' ')) ?: 'your area';
    }

    public function name(RepairJobOffer $offer): string
    {
        $offer->loadMissing('contractor.contractorProfile');

        return $offer->contractor?->contractorProfile?->displayName() ?: 'A contractor';
    }

    private function assertAssignable(RepairIssue $repair): void
    {
        app(\App\Services\Repairs\RepairDeskService::class)->assertMarketplaceAvailable($repair);
        if ($repair->awarded_offer_id) {
            throw new ContractorMarketplaceException('This repair already has an accepted contractor.');
        }
        if (in_array($repair->status, ['Closed', 'Invoice Paid', 'Invoice Received', 'Cancelled', 'Rejected', 'Work Completed', 'Completed'], true)) {
            throw new ContractorMarketplaceException('This repair is already finished.');
        }
    }

    public function assertContractor(RepairJobOffer $offer, User $user): void
    {
        if ((int) $offer->contractor_user_id !== (int) $user->id) {
            throw new ContractorMarketplaceException('You cannot change this job.');
        }
    }

    public function assertLandlord(RepairJobOffer $offer, User $user): void
    {
        if ((int) $offer->account_id !== (int) current_account_id() && ! $user->isSuperAdmin()) {
            throw new ContractorMarketplaceException('This offer belongs to another account.');
        }
    }

    private function expireOne(RepairJobOffer $offer): void
    {
        if ($offer->isAwaitingResponse() && $offer->invite_expires_at?->isPast()) {
            $this->transition($offer, RepairJobOffer::EXPIRED, null, 'No response before the deadline.');
        }
        if ($offer->status === RepairJobOffer::OFFER_SENT && $offer->offer_expires_at?->isPast()) {
            $this->transition($offer, RepairJobOffer::OFFER_EXPIRED, null, 'Offer validity ended.');
            $this->notifyOfferExpired($offer);
        }
    }

    private function notifyOfferExpired(RepairJobOffer $offer): void
    {
        $offer->loadMissing('contractor');
        $this->notify(
            CrmNotificationEvent::RepairJobOfferWithdrawn,
            $offer,
            [$offer->contractor],
            'Your offer has expired',
            'Your offer was not accepted before it expired. The landlord can ask you to reconfirm it.',
            'offer-expired-contractor-'.$offer->id,
            route('contractor.jobs.show', $offer)
        );
        $this->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobOfferWithdrawn,
            'An offer expired',
            $this->name($offer).'’s offer expired before you accepted it. You can ask them to reconfirm.',
            'offer-expired-landlord-'.$offer->id
        );
    }

    private function transition(RepairJobOffer $offer, string $to, ?User $actor, ?string $note, array $snapshot = []): void
    {
        $from = $offer->status;
        $offer->status = $to;
        if (in_array($to, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::DECLINED, RepairJobOffer::NOT_SELECTED, RepairJobOffer::CANCELLED], true)) {
            $offer->decided_at = now();
        }
        $offer->save();
        $this->log($offer, $from, $to, $actor, $note, $snapshot);
    }

    public function log(RepairJobOffer $offer, ?string $from, string $to, ?User $actor, ?string $note, array $snapshot = []): void
    {
        RepairJobOfferEvent::create([
            'repair_job_offer_id' => $offer->id,
            'actor_user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'snapshot' => $snapshot ?: null,
        ]);
    }

    /**
     * @param  array<int, string>|null  $channels
     */
    public function notifyLandlord(RepairJobOffer $offer, CrmNotificationEvent $event, string $subject, string $notice, string $milestone, ?array $channels = null): void
    {
        $offer->loadMissing('invitedBy', 'repairIssue');
        $this->notify($event, $offer, [$offer->invitedBy], $subject, $notice, $milestone, $offer->repairIssue ? route('admin.property_repairs.show', $offer->repair_issue_id) : null, $channels);
    }

    /**
     * @param  array<int, User|null>  $recipients
     * @param  array<int, string>|null  $channels
     */
    public function notify(CrmNotificationEvent $event, ?RepairJobOffer $offer, array $recipients, string $subject, string $notice, string $milestone, ?string $actionUrl = null, ?array $channels = null): void
    {
        $recipients = array_values(array_filter($recipients));
        if (! $offer || $recipients === [] || ! $offer->repair_issue_id) {
            return;
        }
        $offer->loadMissing('repairIssue');
        if (! $offer->repairIssue) {
            return;
        }
        try {
            $this->notifications->dispatch($event, $offer->repairIssue, array_filter([
                'account_id' => $offer->account_id,
                'recipients' => $recipients,
                'notice_subject' => $subject,
                'notice' => $notice,
                'repair_reference' => $offer->repairIssue->reference_number ?: ('#'.$offer->repair_issue_id),
                'milestone' => $milestone,
                'action_url' => $actionUrl,
                'channels' => $channels,
            ], fn ($value) => $value !== null));
        } catch (\Throwable $exception) {
            Log::error('Contractor marketplace notification failed', [
                'offer_id' => $offer->id,
                'event' => $event->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function mailProfile(User $user, string $subject, string $html): void
    {
        $this->mail->toUser($user, $subject, $html);
    }
}
