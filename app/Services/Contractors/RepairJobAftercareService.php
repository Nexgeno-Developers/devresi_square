<?php

namespace App\Services\Contractors;

use App\Enums\CrmNotificationEvent;
use App\Models\Account;
use App\Models\RepairContractorRequest;
use App\Models\RepairJobInvoice;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\RepairJobVariation;
use App\Models\User;
use App\Support\ContractorSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Everything between "job accepted" and "paid": questions, variations, reschedules, completion, ratings and invoices. */
class RepairJobAftercareService
{
    public const PHOTO_DISK = 'local';

    public const MAX_PHOTOS = 6;

    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly ContractorMail $mail,
    ) {}

    // ── Questions ───────────────────────────────────────────────────────────

    public function postMessage(RepairJobOffer $offer, User $author, string $side, string $body): RepairJobMessage
    {
        $side === RepairJobMessage::CONTRACTOR ? $this->offers->assertContractor($offer, $author) : $this->offers->assertLandlord($offer, $author);
        if (! $offer->canMessage()) {
            throw new ContractorMarketplaceException('This job is closed, so messages are switched off.');
        }
        $body = trim($body);
        if ($body === '') {
            throw new ContractorMarketplaceException('Write a message first.');
        }
        $masked = false;
        if (! $offer->unlocksAccess()) {
            [$body, $masked] = self::mask($body);
        }
        $message = RepairJobMessage::create([
            'repair_job_offer_id' => $offer->id,
            'user_id' => $author->id,
            'side' => $side,
            'body' => $body,
            'masked' => $masked,
        ]);

        $preview = Str::limit($body, 160);
        if ($side === RepairJobMessage::CONTRACTOR) {
            $this->offers->notifyLandlord($offer, CrmNotificationEvent::RepairJobMessage, 'Question from '.$this->offers->name($offer), $preview, 'message-'.$message->id);
        } else {
            $this->offers->notify(CrmNotificationEvent::RepairJobMessage, $offer, [$offer->contractor], 'Reply from the landlord', $preview, 'message-'.$message->id, route('contractor.jobs.show', $offer).'#messages');
        }

        return $message;
    }

    public function markRead(RepairJobOffer $offer, string $readerSide): void
    {
        $offer->messages()->where('side', '!=', $readerSide)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * Strip phone numbers, emails and links so nobody goes around the platform before award.
     *
     * @return array{0: string, 1: bool}
     */
    public static function mask(string $body): array
    {
        $hidden = '[hidden until the job is accepted]';
        $count = 0;
        $body = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $hidden, $body, -1, $emails);
        $body = preg_replace('/\b(?:https?:\/\/|www\.)\S+/i', $hidden, $body, -1, $links);
        $body = preg_replace_callback('/\+?\d[\d\s().-]{7,}\d/', function ($match) use ($hidden, &$count) {
            if (strlen(preg_replace('/\D/', '', $match[0])) < 10) {
                return $match[0];
            }
            $count++;

            return $hidden;
        }, $body);

        return [$body, ($emails + $links + $count) > 0];
    }

    // ── Variations ──────────────────────────────────────────────────────────

    public function requestVariation(RepairJobOffer $offer, User $contractor, float $net, string $reason): RepairJobVariation
    {
        $this->offers->assertContractor($offer, $contractor);
        $this->assertActiveJob($offer);
        if (in_array($offer->job_status, [RepairJobOffer::JOB_CONFIRMED, RepairJobOffer::JOB_INVOICED], true)) {
            throw new ContractorMarketplaceException('The landlord already confirmed this job. Talk to them before invoicing extra.');
        }
        if ($net <= 0) {
            throw new ContractorMarketplaceException('Enter the extra cost.');
        }
        if ($offer->variations()->where('status', RepairJobVariation::PENDING)->exists()) {
            throw new ContractorMarketplaceException('Wait for the landlord to answer your last change request.');
        }
        $rate = $contractor->contractorProfile?->vat_registered ? (float) ($offer->vat_rate ?? 20) : 0.0;
        $vat = round($net * $rate / 100, 2);
        $variation = RepairJobVariation::create([
            'repair_job_offer_id' => $offer->id,
            'requested_by' => $contractor->id,
            'reason' => trim($reason),
            'net_amount' => round($net, 2),
            'vat_rate' => $rate,
            'vat_amount' => $vat,
            'gross_amount' => round($net + $vat, 2),
            'status' => RepairJobVariation::PENDING,
        ]);
        $this->offers->log($offer, $offer->status, $offer->status, $contractor, 'Asked for extra work: £'.number_format($variation->gross_amount, 2).'. '.$variation->reason);
        $this->offers->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobVariation,
            'Extra work needs your approval',
            $this->offers->name($offer).' found more work: '.$variation->reason.'. Extra £'.number_format((float) $variation->gross_amount, 2).($rate > 0 ? ' inc VAT' : '').'. Nothing extra is billable until you approve.',
            'variation-'.$variation->id
        );

        return $variation;
    }

    public function decideVariation(RepairJobVariation $variation, User $landlord, bool $approve, ?string $note = null): void
    {
        $offer = $variation->offer;
        $this->offers->assertLandlord($offer, $landlord);
        if ($variation->status !== RepairJobVariation::PENDING) {
            throw new ContractorMarketplaceException('This change was already answered.');
        }
        $variation->forceFill([
            'status' => $approve ? RepairJobVariation::APPROVED : RepairJobVariation::DECLINED,
            'decided_by' => $landlord->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ])->save();
        $label = '£'.number_format((float) $variation->gross_amount, 2);
        $this->offers->log($offer, $offer->status, $offer->status, $landlord, ($approve ? 'Approved' : 'Declined').' extra work of '.$label.'.'.($note ? ' '.$note : ''));
        $this->offers->notify(
            CrmNotificationEvent::RepairJobVariation,
            $offer,
            [$offer->contractor],
            $approve ? 'Extra work approved' : 'Extra work declined',
            $approve
                ? 'The landlord approved '.$label.' of extra work. New job total £'.number_format($offer->fresh('variations')->finalGross(), 2).'.'
                : 'The landlord declined the extra work'.($note ? ': '.$note : '.').' Only the agreed price is billable.',
            'variation-decided-'.$variation->id,
            route('contractor.jobs.show', $offer)
        );
    }

    public function withdrawVariation(RepairJobVariation $variation, User $contractor): void
    {
        $this->offers->assertContractor($variation->offer, $contractor);
        if ($variation->status !== RepairJobVariation::PENDING) {
            throw new ContractorMarketplaceException('This change was already answered.');
        }
        $variation->forceFill(['status' => RepairJobVariation::WITHDRAWN, 'decided_at' => now()])->save();
        $this->offers->log($variation->offer, $variation->offer->status, $variation->offer->status, $contractor, 'Withdrew the extra work request.');
        $this->offers->notifyLandlord(
            $variation->offer,
            CrmNotificationEvent::RepairJobVariation,
            'Extra work request withdrawn',
            $this->offers->name($variation->offer).' withdrew the extra work request.',
            'variation-withdrawn-'.$variation->id
        );
    }

    // ── Reschedule ──────────────────────────────────────────────────────────

    /**
     * Landlord or tenant can't make the booked time and offers new windows.
     *
     * @param  array<int, string>  $slots
     */
    public function requestReschedule(RepairJobOffer $offer, ?User $actor, string $side, array $slots, ?string $note = null): void
    {
        if ($side === 'landlord') {
            $this->offers->assertLandlord($offer, $actor);
        }
        $this->assertVisitBooked($offer);
        if ($offer->isFinished()) {
            throw new ContractorMarketplaceException('This job is already finished.');
        }
        $times = $this->futureSlots($slots);
        if ($times === []) {
            throw new ContractorMarketplaceException('Pick at least one new time in the future.');
        }
        $offer->forceFill([
            'reschedule_requested_at' => now(),
            'reschedule_requested_by' => $side,
            'reschedule_slots' => array_map(fn (Carbon $t) => $t->toIso8601String(), $times),
            'reschedule_note' => $note ? Str::limit(trim($note), 490) : null,
        ])->save();
        $list = collect($times)->map(fn (Carbon $t) => $t->format('D j M, H:i'))->implode(' or ');
        $who = $side === 'tenant' ? 'The tenant' : 'The landlord';
        $this->offers->log($offer, $offer->status, $offer->status, $actor, $who.' asked to move the visit to '.$list.'.');
        $this->offers->notify(
            CrmNotificationEvent::RepairJobRescheduled,
            $offer,
            [$offer->contractor],
            'New visit time needed',
            $who.' can no longer make the visit. They offered '.$list.'. Pick one or suggest another time.',
            'reschedule-request-'.$offer->id.'-'.now()->timestamp,
            route('contractor.jobs.show', $offer)
        );
        if ($side === 'tenant') {
            $this->offers->notifyLandlord($offer, CrmNotificationEvent::RepairJobRescheduled, 'Tenant asked to move the visit', 'The tenant offered '.$list.'. The contractor will pick one.', 'reschedule-tenant-'.$offer->id.'-'.now()->timestamp);
        }
    }

    /** Contractor books a new time: one of the offered windows, or a counter-proposal the tenant then confirms. */
    public function reschedule(RepairJobOffer $offer, User $contractor, string $slot): void
    {
        $this->offers->assertContractor($offer, $contractor);
        $this->assertVisitBooked($offer);
        if ($offer->isFinished()) {
            throw new ContractorMarketplaceException('This job is already finished.');
        }
        $when = rescue(fn () => Carbon::parse($slot), null, false);
        if (! $when || $when->isPast()) {
            throw new ContractorMarketplaceException('Pick a visit time in the future.');
        }
        $offered = collect($offer->reschedule_slots ?? [])->contains(fn ($s) => Carbon::parse($s)->equalTo($when));
        $tenantPicked = $offered && $offer->reschedule_requested_by === 'tenant';
        $jobStatus = $offer->status !== RepairJobOffer::JOB_ACCEPTED || $offer->job_status === RepairJobOffer::JOB_IN_PROGRESS
            ? $offer->job_status
            : RepairJobOffer::JOB_SCHEDULED;
        $offer->forceFill([
            'scheduled_for' => $when,
            'job_status' => $jobStatus,
            'tenant_confirmed_at' => $tenantPicked ? now() : null,
            'reschedule_requested_at' => null,
            'reschedule_requested_by' => null,
            'reschedule_slots' => null,
            'reschedule_note' => null,
        ])->save();
        $this->offers->log($offer, $offer->status, $offer->status, $contractor, 'Visit moved to '.$when->format('j M Y H:i').($offered ? ' (offered time).' : ' (new proposal).'));

        $offer->loadMissing('repairIssue.property');
        $address = $offer->repairIssue
            ? $this->offers->place($offer->repairIssue, $offer->unlocksAccess())
            : '';
        $this->mail->toUser($contractor, 'Visit moved: '.($offer->repairIssue?->reference_number ?: 'repair'), '<p>The visit is now '.e($when->format('D j M Y, H:i')).'.</p>', [$this->offers->ics($offer, $when, $address)]);
        $this->offers->notifyLandlord($offer, CrmNotificationEvent::RepairJobRescheduled, 'Visit moved', $this->offers->name($offer).' moved the visit to '.$when->format('D j M, H:i').'. If the price changes, they will send an extra work request for you to approve.', 'rescheduled-'.$offer->id.'-'.$when->timestamp);
        $this->offers->notifyTenantVisit($offer, 'Your repair visit has moved');
    }

    // ── Completion, rating ──────────────────────────────────────────────────

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function complete(RepairJobOffer $offer, User $contractor, ?string $note, array $photos = []): void
    {
        $this->offers->assertContractor($offer, $contractor);
        $photos = array_slice(array_filter($photos), 0, self::MAX_PHOTOS);
        if ($photos === []) {
            throw new ContractorMarketplaceException('Add a photo of the finished work before marking the job complete.');
        }
        $stored = [];
        foreach ($photos as $photo) {
            $stored[] = $photo->storeAs('repair-jobs/'.$offer->id, Str::uuid().'.'.($photo->guessExtension() ?: 'jpg'), self::PHOTO_DISK);
        }
        try {
            $this->offers->progress($offer, $contractor, 'complete', ['completion_note' => $note]);
        } catch (\Throwable $exception) {
            foreach ($stored as $path) {
                Storage::disk(self::PHOTO_DISK)->delete($path);
            }
            throw $exception;
        }
        if ($stored !== []) {
            $offer->forceFill(['completion_photos' => array_values(array_merge($offer->completion_photos ?? [], $stored))])->save();
        }
    }

    public function rate(RepairJobOffer $offer, User $landlord, int $stars, ?string $comment = null): void
    {
        $this->offers->assertLandlord($offer, $landlord);
        if (! in_array($offer->job_status, [RepairJobOffer::JOB_CONFIRMED, RepairJobOffer::JOB_INVOICED], true)) {
            throw new ContractorMarketplaceException('Confirm the job is done before rating it.');
        }
        if ($offer->rated_at) {
            throw new ContractorMarketplaceException('You already rated this job.');
        }
        if ($stars < 1 || $stars > 5) {
            throw new ContractorMarketplaceException('Pick between 1 and 5 stars.');
        }
        $offer->forceFill(['rating' => $stars, 'rating_comment' => $comment ? Str::limit(trim($comment), 990) : null, 'rated_at' => now()])->save();
        $this->offers->log($offer, $offer->status, $offer->status, $landlord, 'Rated '.$stars.'/5.');
        $this->offers->notify(CrmNotificationEvent::RepairJobRated, $offer, [$offer->contractor], 'You were rated '.$stars.' out of 5', $comment ? '“'.Str::limit($comment, 160).'”' : 'The landlord rated your work.', 'rated-'.$offer->id, route('contractor.jobs.show', $offer));
    }

    // ── Invoice ─────────────────────────────────────────────────────────────

    public function issueInvoice(RepairJobOffer $offer, User $contractor, ?float $finalNet = null, ?string $notes = null): RepairJobInvoice
    {
        $this->offers->assertContractor($offer, $contractor);
        $offer->loadMissing('variations', 'repairIssue.repairCategory', 'contractor.contractorProfile');
        if ($offer->status !== RepairJobOffer::JOB_ACCEPTED || $offer->job_status !== RepairJobOffer::JOB_CONFIRMED) {
            throw new ContractorMarketplaceException('You can invoice once the landlord confirms the job is done.');
        }
        if ($offer->invoice()->exists()) {
            throw new ContractorMarketplaceException('This job already has an invoice.');
        }
        if ($offer->variations->where('status', RepairJobVariation::PENDING)->isNotEmpty()) {
            throw new ContractorMarketplaceException('Wait for the landlord to answer your extra work request first.');
        }

        $profile = $offer->contractor?->contractorProfile;
        if (! $profile?->hasBankDetails()) {
            throw new ContractorMarketplaceException('Add your bank details before you send an invoice.');
        }

        $rate = (float) ($offer->vat_rate ?? 0);
        $net = (float) $offer->net_amount;
        if ($finalNet !== null && $offer->price_is_estimate) {
            if ($finalNet <= 0) {
                throw new ContractorMarketplaceException('Enter the final price.');
            }
            if ($offer->price_cap && $finalNet > (float) $offer->price_cap) {
                throw new ContractorMarketplaceException('The final price is over the agreed cap of £'.number_format((float) $offer->price_cap, 2).'. Ask the landlord to approve extra work first.');
            }
            $net = $finalNet;
        }
        $line = fn (string $description, float $lineNet, float $lineRate) => [
            'description' => $description,
            'net' => round($lineNet, 2),
            'vat_rate' => $lineRate,
            'vat' => round($lineNet * $lineRate / 100, 2),
            'gross' => round($lineNet + $lineNet * $lineRate / 100, 2),
        ];
        $work = trim((string) ($offer->completion_note ?: $offer->repairIssue?->description));
        $trade = $offer->repairIssue?->repairCategory?->name ?: 'Repair';
        $lines = [$line($trade.($work !== '' ? ' – '.Str::limit($work, 180) : ''), $net, $rate)];
        foreach ($offer->variations->where('status', RepairJobVariation::APPROVED) as $variation) {
            $lines[] = $line('Extra work: '.Str::limit($variation->reason, 160), (float) $variation->net_amount, (float) $variation->vat_rate);
        }

        $number = 'RJ-'.str_pad((string) $offer->id, 6, '0', STR_PAD_LEFT);
        $account = Account::query()->with('owner')->find($offer->account_id);
        $owner = $account?->owner;
        $property = $offer->repairIssue?->property;
        $invoice = DB::transaction(function () use ($offer, $contractor, $lines, $profile, $notes, $number, $account, $owner, $property) {
            $invoice = RepairJobInvoice::create([
                'repair_job_offer_id' => $offer->id,
                'account_id' => $offer->account_id,
                'contractor_user_id' => $contractor->id,
                'number' => $number,
                'issued_on' => today(),
                'due_on' => today()->addDays(max(0, (int) ContractorSettings::get('invoice_due_days'))),
                'supplied_on' => $offer->completed_at?->toDateString(),
                'lines' => $lines,
                'net_amount' => collect($lines)->sum('net'),
                'vat_amount' => collect($lines)->sum('vat'),
                'gross_amount' => collect($lines)->sum('gross'),
                'vat_number' => $profile->vat_registered ? $profile->vat_number : null,
                'cis_status' => $profile->cis_status,
                'supplier' => [
                    'name' => $profile->displayName(),
                    'legal_name' => $profile->legal_name,
                    'address' => collect([$profile->address_line, $profile->city, $profile->postcode])->filter()->implode(', '),
                    'phone' => $profile->phone,
                    'email' => $contractor->email,
                    'company_number' => $profile->company_number,
                    'vat_number' => $profile->vat_registered ? $profile->vat_number : null,
                ],
                'bill_to' => [
                    'name' => $account?->account_name ?: $owner?->name,
                    'contact' => $owner?->name,
                    'email' => $account?->billing_email ?: $owner?->email,
                    'address' => collect([$owner?->address_line_1, $owner?->address_line_2, $owner?->city, $owner?->postcode])->filter()->implode(', '),
                ],
                'site' => [
                    'address' => $property?->full_address ?: '',
                ],
                'payment' => [
                    'account_name' => $profile->bank_account_name,
                    'sort_code' => $profile->formattedSortCode(),
                    'account_number' => $profile->bank_account_number,
                    'bank_name' => $profile->bank_name,
                    'reference' => $number,
                ],
                'notes' => $notes ? Str::limit(trim($notes), 2000) : null,
                'status' => RepairJobInvoice::ISSUED,
            ]);
            $offer->forceFill(['job_status' => RepairJobOffer::JOB_INVOICED])->save();
            RepairContractorRequest::query()->where('repair_issue_id', $offer->repair_issue_id)->update(['status' => RepairContractorRequest::COMPLETED]);

            return $invoice;
        });
        $this->offers->log($offer, $offer->status, $offer->status, $contractor, 'Invoice '.$invoice->number.' issued for £'.number_format((float) $invoice->gross_amount, 2).'.');
        $this->offers->notifyLandlord(
            $offer,
            CrmNotificationEvent::RepairJobInvoiced,
            'Invoice from '.$this->offers->name($offer),
            'Invoice '.$invoice->number.' for £'.number_format((float) $invoice->gross_amount, 2).', due '.$invoice->due_on->format('j M Y').'.',
            'invoice-'.$invoice->id
        );

        return $invoice;
    }

    public function markPaid(RepairJobInvoice $invoice, User $landlord): void
    {
        $this->offers->assertLandlord($invoice->offer, $landlord);
        if ($invoice->status !== RepairJobInvoice::ISSUED) {
            throw new ContractorMarketplaceException('This invoice is not waiting for payment.');
        }
        $invoice->forceFill(['status' => RepairJobInvoice::PAID, 'paid_at' => now(), 'paid_by' => $landlord->id])->save();
        $this->offers->log($invoice->offer, $invoice->offer->status, $invoice->offer->status, $landlord, 'Invoice '.$invoice->number.' marked paid.');
        $this->offers->notify(CrmNotificationEvent::RepairJobInvoiced, $invoice->offer, [$invoice->contractor], 'Invoice paid', 'The landlord marked invoice '.$invoice->number.' as paid.', 'invoice-paid-'.$invoice->id, route('contractor.jobs.invoice', $invoice->offer));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function assertVisitBooked(RepairJobOffer $offer): void
    {
        if (! in_array($offer->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
            throw new ContractorMarketplaceException('There is no booked visit to move.');
        }
    }

    private function assertActiveJob(RepairJobOffer $offer): void
    {
        if ($offer->status !== RepairJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('This job is not active.');
        }
    }

    /**
     * @param  array<int, mixed>  $slots
     * @return array<int, Carbon>
     */
    private function futureSlots(array $slots): array
    {
        return collect($slots)
            ->filter(fn ($s) => is_string($s) && trim($s) !== '')
            ->map(fn ($s) => rescue(fn () => Carbon::parse($s), null, false))
            ->filter(fn ($t) => $t && $t->isFuture())
            ->unique(fn (Carbon $t) => $t->timestamp)
            ->sort()
            ->take(3)
            ->values()
            ->all();
    }
}
