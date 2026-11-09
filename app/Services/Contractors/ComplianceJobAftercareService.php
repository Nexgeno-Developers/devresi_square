<?php

namespace App\Services\Contractors;

use App\Models\ComplianceBooking;
use App\Models\ComplianceJobMessage;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceJobVariation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Questions, extra work, reschedule, cancel, dispute and rating for a compliance check. */
class ComplianceJobAftercareService
{
    public function __construct(
        private readonly ContractorMail $mail,
    ) {}

    public function postMessage(ComplianceJobOffer $offer, User $author, string $side, string $body): ComplianceJobMessage
    {
        $this->assertSide($offer, $author, $side);
        if (! $offer->canMessage()) {
            throw new ContractorMarketplaceException('This check is closed, so messages are switched off.');
        }
        $body = trim($body);
        if ($body === '') {
            throw new ContractorMarketplaceException('Write a message first.');
        }
        $masked = false;
        if (! $offer->showsAddress()) {
            [$body, $masked] = RepairJobAftercareService::mask($body);
        }
        $message = ComplianceJobMessage::create([
            'compliance_job_offer_id' => $offer->id,
            'user_id' => $author->id,
            'side' => $side,
            'body' => $body,
            'masked' => $masked,
        ]);

        $preview = Str::limit($body, 160);
        $check = $this->checkName($offer);
        if ($side === ComplianceJobMessage::CONTRACTOR) {
            $landlord = User::query()->find($offer->invited_by);
            $url = $this->landlordUrl($offer);
            $this->mail->toUser($landlord, 'Message about '.$check, '<p>'.e($offer->contractorName()).': '.e($preview).'</p>'.($url ? '<p><a href="'.e($url).'">Open the booking</a></p>' : ''));
            $this->mail->bell($landlord, 'Message about '.$check, $offer->contractorName().': '.$preview, $url, (int) $offer->account_id, 'compliance.booking');
        } else {
            $url = route('contractor.jobs.compliance.show', $offer).'#messages';
            $this->mail->toUser($offer->contractor, 'Reply about '.$check, '<p>'.e($preview).'</p><p><a href="'.e($url).'">Open the job</a></p>');
            $this->mail->bell($offer->contractor, 'Reply about '.$check, $preview, $url, (int) $offer->account_id, 'compliance.booking');
        }

        return $message;
    }

    public function markRead(ComplianceJobOffer $offer, string $readerSide): void
    {
        $offer->messages()->where('side', '!=', $readerSide)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function requestVariation(ComplianceJobOffer $offer, User $contractor, float $net, string $reason): ComplianceJobVariation
    {
        $this->assertSide($offer, $contractor, ComplianceJobMessage::CONTRACTOR);
        if ($offer->status !== ComplianceJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('This check is not active.');
        }
        if (in_array($offer->job_status, [ComplianceJobOffer::JOB_CONFIRMED, ComplianceJobOffer::JOB_INVOICED], true)) {
            throw new ContractorMarketplaceException('The landlord already confirmed this check. Talk to them before invoicing extra.');
        }
        if ($net <= 0) {
            throw new ContractorMarketplaceException('Enter the extra cost.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new ContractorMarketplaceException('Say what the extra work is.');
        }
        if ($offer->variations()->where('status', ComplianceJobVariation::PENDING)->exists()) {
            throw new ContractorMarketplaceException('Wait for the landlord to answer your last change request.');
        }

        $rate = $contractor->contractorProfile?->vat_registered ? (float) ($offer->vat_rate ?? 20) : 0.0;
        $vat = round($net * $rate / 100, 2);
        $variation = ComplianceJobVariation::create([
            'compliance_job_offer_id' => $offer->id,
            'requested_by' => $contractor->id,
            'reason' => $reason,
            'net_amount' => round($net, 2),
            'vat_rate' => $rate,
            'vat_amount' => $vat,
            'gross_amount' => round($net + $vat, 2),
            'status' => ComplianceJobVariation::PENDING,
        ]);

        $check = $this->checkName($offer);
        $url = $this->landlordUrl($offer);
        $notice = $offer->contractorName().' found more work: '.$variation->reason.'. Extra £'.number_format((float) $variation->gross_amount, 2).($rate > 0 ? ' inc VAT' : '').'. Nothing extra is billable until you approve.';
        $landlord = User::query()->find($offer->invited_by);
        $this->mail->toUser($landlord, 'Extra work on '.$check, '<p>'.e($notice).'</p>'.($url ? '<p><a href="'.e($url).'">Review the request</a></p>' : ''));
        $this->mail->bell($landlord, 'Extra work on '.$check, $notice, $url, (int) $offer->account_id, 'compliance.booking');

        return $variation;
    }

    public function decideVariation(ComplianceJobVariation $variation, User $landlord, bool $approve, ?string $note = null): void
    {
        $offer = $variation->offer;
        $this->assertSide($offer, $landlord, ComplianceJobMessage::LANDLORD);
        if ($variation->status !== ComplianceJobVariation::PENDING) {
            throw new ContractorMarketplaceException('This change was already answered.');
        }
        $variation->forceFill([
            'status' => $approve ? ComplianceJobVariation::APPROVED : ComplianceJobVariation::DECLINED,
            'decided_by' => $landlord->id,
            'decided_at' => now(),
            'decision_note' => $note ? Str::limit(trim($note), 500, '') : null,
        ])->save();

        $label = '£'.number_format((float) $variation->gross_amount, 2);
        $check = $this->checkName($offer);
        $url = route('contractor.jobs.compliance.show', $offer);
        $notice = $approve
            ? 'The landlord approved '.$label.' of extra work. New total £'.number_format($offer->fresh('variations')->finalGross(), 2).'.'
            : 'The landlord declined the extra work'.($note ? ': '.$note : '.').' Only the agreed price is billable.';
        $this->mail->toUser($offer->contractor, ($approve ? 'Extra work approved' : 'Extra work declined').': '.$check, '<p>'.e($notice).'</p><p><a href="'.e($url).'">Open the job</a></p>');
        $this->mail->bell($offer->contractor, $approve ? 'Extra work approved' : 'Extra work declined', $notice, $url, (int) $offer->account_id, 'compliance.booking');
    }

    public function withdrawVariation(ComplianceJobVariation $variation, User $contractor): void
    {
        $offer = $variation->offer;
        $this->assertSide($offer, $contractor, ComplianceJobMessage::CONTRACTOR);
        if ((int) $variation->compliance_job_offer_id !== (int) $offer->id) {
            throw new ContractorMarketplaceException('This change is not on your job.');
        }
        if ($variation->status !== ComplianceJobVariation::PENDING) {
            throw new ContractorMarketplaceException('This change was already answered.');
        }
        $variation->forceFill(['status' => ComplianceJobVariation::WITHDRAWN, 'decided_at' => now()])->save();

        $check = $this->checkName($offer);
        $url = $this->landlordUrl($offer);
        $landlord = User::query()->find($offer->invited_by);
        $notice = $offer->contractorName().' withdrew the extra work request.';
        $this->mail->toUser($landlord, 'Extra work withdrawn: '.$check, '<p>'.e($notice).'</p>');
        $this->mail->bell($landlord, 'Extra work withdrawn', $notice, $url, (int) $offer->account_id, 'compliance.booking');
    }

    /**
     * @param  array<int, string>  $slots
     */
    public function requestReschedule(ComplianceJobOffer $offer, User $landlord, array $slots, ?string $note = null): void
    {
        $this->assertSide($offer, $landlord, ComplianceJobMessage::LANDLORD);
        $this->assertVisitOpen($offer);
        $times = $this->futureSlots($slots);
        if ($times === []) {
            throw new ContractorMarketplaceException('Pick at least one new time in the future.');
        }
        $offer->forceFill([
            'reschedule_requested_at' => now(),
            'reschedule_requested_by' => 'landlord',
            'reschedule_slots' => array_map(fn (Carbon $time) => $time->toIso8601String(), $times),
            'reschedule_note' => $note ? Str::limit(trim($note), 490, '') : null,
        ])->save();

        $list = collect($times)->map(fn (Carbon $time) => $time->format('D j M, H:i'))->implode(' or ');
        $check = $this->checkName($offer);
        $url = route('contractor.jobs.compliance.show', $offer);
        $notice = 'The landlord can no longer make the visit. They offered '.$list.'. Pick one or suggest another time.';
        $this->mail->toUser($offer->contractor, 'New visit time for '.$check, '<p>'.e($notice).'</p><p><a href="'.e($url).'">Open the job</a></p>');
        $this->mail->bell($offer->contractor, 'New visit time needed', $notice, $url, (int) $offer->account_id, 'compliance.booking');
    }

    public function acceptReschedule(ComplianceJobOffer $offer, User $contractor, string $slot): void
    {
        $this->assertSide($offer, $contractor, ComplianceJobMessage::CONTRACTOR);
        $this->assertVisitOpen($offer);
        $when = rescue(fn () => Carbon::parse($slot), null, false);
        if (! $when || $when->isPast()) {
            throw new ContractorMarketplaceException('Pick a visit time in the future.');
        }
        $offered = collect($offer->reschedule_slots ?? [])->contains(fn ($item) => Carbon::parse($item)->equalTo($when));
        if (! $offered) {
            throw new ContractorMarketplaceException('Pick one of the times the landlord offered, or suggest your own visit time.');
        }
        $offer->forceFill([
            'scheduled_for' => $when,
            'job_status' => $offer->job_status === ComplianceJobOffer::JOB_IN_PROGRESS
                ? ComplianceJobOffer::JOB_IN_PROGRESS
                : ComplianceJobOffer::JOB_SCHEDULED,
            'reschedule_requested_at' => null,
            'reschedule_requested_by' => null,
            'reschedule_slots' => null,
            'reschedule_note' => null,
        ])->save();

        $check = $this->checkName($offer);
        $url = $this->landlordUrl($offer);
        $whenLabel = $when->format('D j M, H:i');
        $landlord = User::query()->find($offer->invited_by);
        $notice = $offer->contractorName().' accepted the visit time '.$whenLabel.'.';
        $this->mail->toUser($landlord, 'Visit agreed: '.$check, '<p>'.e($notice).'</p>'.($url ? '<p><a href="'.e($url).'">Open the booking</a></p>' : ''));
        $this->mail->bell($landlord, 'Visit agreed', $notice, $url, (int) $offer->account_id, 'compliance.booking');
    }

    public function cancel(ComplianceJobOffer $offer, User $actor, string $side, ?string $reason = null): void
    {
        $this->assertSide($offer, $actor, $side);
        $reason = $reason ? Str::limit(trim($reason), 500, '') : null;

        DB::transaction(function () use ($offer, $side, $reason) {
            $booking = ComplianceBooking::query()->whereKey($offer->compliance_booking_id)->lockForUpdate()->firstOrFail();
            $locked = ComplianceJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ComplianceJobOffer::JOB_ACCEPTED) {
                throw new ContractorMarketplaceException('Only a booked check can be cancelled.');
            }
            if ($locked->isFinished()) {
                throw new ContractorMarketplaceException('This check is already complete. Report a problem instead.');
            }
            $locked->forceFill([
                'status' => ComplianceJobOffer::CANCELLED,
                'cancel_reason' => $reason,
                'decided_at' => now(),
                'reschedule_requested_at' => null,
                'reschedule_requested_by' => null,
                'reschedule_slots' => null,
                'reschedule_note' => null,
            ])->save();
            if ((int) $booking->awarded_offer_id === (int) $locked->id) {
                $booking->forceFill([
                    'status' => ComplianceBooking::SENT,
                    'awarded_offer_id' => null,
                    'awarded_at' => null,
                ])->save();
            }
            $offer->setRawAttributes($locked->getAttributes(), true);
        });

        $offer->loadMissing('contractor');
        $check = $this->checkName($offer);
        $because = $reason ? ': '.$reason : '.';
        if ($side === ComplianceJobMessage::CONTRACTOR) {
            $url = $this->landlordUrl($offer);
            $landlord = User::query()->find($offer->invited_by);
            $notice = $offer->contractorName().' cancelled the check'.$because.' You can send it to another contractor.';
            $this->mail->toUser($landlord, 'Check cancelled: '.$check, '<p>'.e($notice).'</p>'.($url ? '<p><a href="'.e($url).'">Open the booking</a></p>' : ''));
            $this->mail->bell($landlord, 'Check cancelled', $notice, $url, (int) $offer->account_id, 'compliance.booking');
        } else {
            $url = route('contractor.jobs.compliance.show', $offer);
            $notice = 'The landlord cancelled this check'.$because;
            $this->mail->toUser($offer->contractor, 'Check cancelled: '.$check, '<p>'.e($notice).'</p><p><a href="'.e($url).'">Open the job</a></p>');
            $this->mail->bell($offer->contractor, 'Check cancelled', $notice, $url, (int) $offer->account_id, 'compliance.booking');
        }
    }

    public function dispute(ComplianceJobOffer $offer, User $actor, string $side, string $reason): void
    {
        $this->assertSide($offer, $actor, $side);
        if ($offer->status !== ComplianceJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('Only a booked check can be disputed.');
        }
        if ($offer->disputed_at) {
            throw new ContractorMarketplaceException('This check is already disputed.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new ContractorMarketplaceException('Say what the problem is.');
        }
        $offer->forceFill([
            'disputed_at' => now(),
            'dispute_reason' => Str::limit($reason, 2000, ''),
        ])->save();

        $check = $this->checkName($offer);
        $this->mail->toAdmins(
            'Disputed compliance check',
            '<p>'.e(ucfirst($side)).' raised a dispute on '.e($check).': '.e($reason).'</p><p><a href="'.e(route('admin.registrations.contractors.jobs')).'">Open disputed jobs</a></p>'
        );
        $other = $side === ComplianceJobMessage::CONTRACTOR ? User::query()->find($offer->invited_by) : $offer->contractor;
        $url = $side === ComplianceJobMessage::CONTRACTOR ? $this->landlordUrl($offer) : route('contractor.jobs.compliance.show', $offer);
        $notice = 'This check was flagged as disputed. Our team will be in touch.';
        $this->mail->toUser($other, 'Check disputed: '.$check, '<p>'.e($notice).'</p><p>'.e($reason).'</p>');
        $this->mail->bell($other, 'Check disputed', $notice.' '.$reason, $url, (int) $offer->account_id, 'compliance.booking');
    }

    public function resolveDispute(ComplianceJobOffer $offer, User $admin, string $note): void
    {
        abort_unless($admin->isSuperAdmin(), 403);
        if (! $offer->disputed_at) {
            throw new ContractorMarketplaceException('This check is not disputed.');
        }
        $note = trim($note);
        if ($note === '') {
            throw new ContractorMarketplaceException('Add a note for the record.');
        }
        $offer->forceFill(['disputed_at' => null])->save();
        $offer->loadMissing('contractor');
        $check = $this->checkName($offer);
        $notice = 'An admin resolved the dispute: '.$note;
        foreach ([$offer->contractor, User::query()->find($offer->invited_by)] as $person) {
            $url = $person && (int) $person->id === (int) $offer->contractor_user_id
                ? route('contractor.jobs.compliance.show', $offer)
                : $this->landlordUrl($offer);
            $this->mail->toUser($person, 'Dispute resolved: '.$check, '<p>'.e($notice).'</p>');
            $this->mail->bell($person, 'Dispute resolved', $notice, $url, (int) $offer->account_id, 'compliance.booking');
        }
    }

    public function rate(ComplianceJobOffer $offer, User $landlord, int $stars, ?string $comment = null): void
    {
        $this->assertSide($offer, $landlord, ComplianceJobMessage::LANDLORD);
        if (! in_array($offer->job_status, [ComplianceJobOffer::JOB_CONFIRMED, ComplianceJobOffer::JOB_INVOICED], true)) {
            throw new ContractorMarketplaceException('Confirm the check is done before rating it.');
        }
        if ($offer->rated_at) {
            throw new ContractorMarketplaceException('You already rated this check.');
        }
        if ($stars < 1 || $stars > 5) {
            throw new ContractorMarketplaceException('Pick between 1 and 5 stars.');
        }
        $comment = $comment ? Str::limit(trim($comment), 1000, '') : null;
        $offer->forceFill([
            'rating' => $stars,
            'rating_comment' => $comment !== '' ? $comment : null,
            'rated_at' => now(),
        ])->save();

        $check = $this->checkName($offer);
        $url = route('contractor.jobs.compliance.show', $offer);
        $notice = $comment ? '“'.Str::limit($comment, 160).'”' : 'The landlord rated your work.';
        $this->mail->toUser($offer->contractor, 'You were rated '.$stars.' out of 5', '<p>'.e($check).'. '.e($notice).'</p><p><a href="'.e($url).'">Open the job</a></p>');
        $this->mail->bell($offer->contractor, 'You were rated '.$stars.' out of 5', $notice, $url, (int) $offer->account_id, 'compliance.booking');
    }

    private function assertSide(ComplianceJobOffer $offer, User $user, string $side): void
    {
        if ($side === ComplianceJobMessage::CONTRACTOR) {
            if ((int) $offer->contractor_user_id !== (int) $user->id) {
                throw new ContractorMarketplaceException('This job is not yours.');
            }

            return;
        }
        if ($side !== ComplianceJobMessage::LANDLORD) {
            throw new ContractorMarketplaceException('Unknown side.');
        }
        $offer->loadMissing('booking');
        if (! $offer->booking || (int) $offer->booking->account_id !== (int) current_account_id()) {
            throw new ContractorMarketplaceException('This check is not on your account.');
        }
    }

    private function assertVisitOpen(ComplianceJobOffer $offer): void
    {
        if ($offer->status !== ComplianceJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('There is no booked visit to move.');
        }
        if ($offer->isFinished()) {
            throw new ContractorMarketplaceException('This check is already finished.');
        }
    }

    /**
     * @param  array<int, mixed>  $slots
     * @return array<int, Carbon>
     */
    private function futureSlots(array $slots): array
    {
        return collect($slots)
            ->filter(fn ($slot) => is_string($slot) && trim($slot) !== '')
            ->map(fn ($slot) => rescue(fn () => Carbon::parse($slot), null, false))
            ->filter(fn ($time) => $time instanceof Carbon && $time->isFuture())
            ->unique(fn (Carbon $time) => $time->timestamp)
            ->sort()
            ->take(3)
            ->values()
            ->all();
    }

    private function checkName(ComplianceJobOffer $offer): string
    {
        $offer->loadMissing('booking.type');

        return $offer->booking?->type?->name ?: 'Compliance check';
    }

    private function landlordUrl(ComplianceJobOffer $offer): ?string
    {
        $offer->loadMissing('booking');

        return $offer->booking ? route('admin.compliance.bookings.show', $offer->booking).'#offer-'.$offer->id : null;
    }
}
