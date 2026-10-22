<?php

namespace App\Services\Portal;

use App\Models\ContractorProfile;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\RepairJobOfferEvent;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Tenant-facing summary and activity for one repair. Prices stay off this story. */
class TenantRepairStory
{
    /**
     * @return array{
     *     title: string,
     *     category: string,
     *     reported: string,
     *     visit: string,
     *     visit_tone: string,
     *     reporter: string,
     *     availability: ?string,
     *     availability_note: string,
     *     offer: ?RepairJobOffer,
     *     when: ?Carbon,
     *     needs_confirm: bool,
     *     activity: array<int, array{at: ?Carbon, actor: string, text: string}>
     * }
     */
    public function for(RepairIssue $repair): array
    {
        $offer = $this->activeOffer($repair);
        $offer?->setRelation('repairIssue', $repair);
        $when = $offer?->agreedVisitAt();
        $needsConfirm = (bool) ($offer && $when && ! $offer->visitIsSettled() && ! $offer->isFinished());
        $title = $repair->isPriorityComplaint()
            ? (string) ($repair->classification_snapshot['title'] ?? $repair->description)
            : (string) ($repair->description ?: 'Repair #'.$repair->id);

        return [
            'title' => $title,
            'category' => $repair->isPriorityComplaint()
                ? 'Priority'
                : (string) ($repair->repairCategory?->name ?: 'Repair'),
            'reported' => $repair->created_at?->format('j M Y') ?: '—',
            'visit' => $this->visitLine($repair, $offer, $when, $needsConfirm),
            'visit_tone' => $needsConfirm ? 'wait' : ($offer && $offer->visitIsSettled() ? 'ok' : 'quiet'),
            'reporter' => $repair->reportedByLandlord()
                ? 'Your landlord reported this for you'
                : 'You reported this',
            'availability' => $repair->tenantAvailabilityLabel(),
            'availability_note' => $this->availabilityNote($repair),
            'offer' => $offer,
            'when' => $when,
            'needs_confirm' => $needsConfirm,
            'activity' => $this->activity($repair),
        ];
    }

    private function activeOffer(RepairIssue $repair): ?RepairJobOffer
    {
        $awarded = $repair->relationLoaded('awardedOffer') ? $repair->awardedOffer : null;
        if ($awarded && in_array($awarded->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
            return $awarded;
        }

        $offers = $repair->relationLoaded('jobOffers') ? $repair->jobOffers : $repair->jobOffers()->get();

        return $offers->first(fn (RepairJobOffer $offer) => in_array($offer->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true))
            ?: $offers->sortByDesc('id')->first();
    }

    private function visitLine(RepairIssue $repair, ?RepairJobOffer $offer, ?Carbon $when, bool $needsConfirm): string
    {
        if ($repair->tenantStatusLabel() === 'Done') {
            return 'Finished';
        }
        if ($repair->tenantStatusLabel() === 'Cancelled') {
            return 'Cancelled';
        }
        if (! $when) {
            return 'No visit booked yet';
        }
        $label = $when->format('D j M, H:i');
        if ($needsConfirm) {
            return 'Confirm '.$label;
        }
        if ($offer && $repair->tenantChoseAvailability() && $repair->coversVisit($when)) {
            return $label.' · your time';
        }
        if ($offer?->tenant_confirmed_at) {
            return $label.' · you confirmed';
        }

        return $label;
    }

    private function availabilityNote(RepairIssue $repair): string
    {
        if (! $repair->tenant_availability) {
            return $repair->reportedByLandlord()
                ? 'No time was given. A contractor’s proposal needs your confirmation.'
                : 'You did not give a time. A contractor’s proposal needs your confirmation.';
        }
        if ($repair->reportedByLandlord()) {
            return 'Your landlord suggested '.$repair->tenantAvailabilityLabel().'. You still need to confirm the visit that is booked.';
        }

        return 'You can be in '.$repair->tenantAvailabilityLabel().'.';
    }

    /**
     * @return array<int, array{at: ?Carbon, actor: string, text: string}>
     */
    private function activity(RepairIssue $repair): array
    {
        $rows = [[
            'at' => $repair->created_at,
            'actor' => $repair->reportedByLandlord() ? 'Landlord' : 'You',
            'text' => $repair->reportedByLandlord()
                ? 'Reported this repair for you.'
                : 'Reported this repair.',
        ]];

        if ($repair->tenant_availability) {
            $rows[] = [
                'at' => $repair->created_at,
                'actor' => $repair->tenantChoseAvailability() ? 'You' : 'Landlord',
                'text' => ($repair->tenantChoseAvailability() ? 'Can be in ' : 'Suggested ').$repair->tenantAvailabilityLabel().'.',
            ];
        }

        $offers = $repair->relationLoaded('jobOffers') ? $repair->jobOffers : collect();
        foreach ($offers as $offer) {
            $events = $offer->relationLoaded('events') ? $offer->events : $offer->events()->get();
            foreach ($events as $event) {
                $text = $this->eventText($event);
                if ($text === null) {
                    continue;
                }
                $rows[] = [
                    'at' => $event->created_at,
                    'actor' => $this->actor($event, $offer, $repair),
                    'text' => $text,
                ];
            }
        }

        return collect($rows)
            ->sortBy(fn (array $row) => $row['at']?->timestamp ?? 0)
            ->values()
            ->all();
    }

    private function eventText(RepairJobOfferEvent $event): ?string
    {
        $note = trim((string) $event->note);
        if ($note === '') {
            return null;
        }
        if (str_contains($note, '£')) {
            return match ($event->to_status) {
                RepairJobOffer::OFFER_SENT => 'Sent an offer.',
                RepairJobOffer::JOB_ACCEPTED => 'The landlord instructed this contractor.',
                default => null,
            };
        }

        return $note;
    }

    private function actor(RepairJobOfferEvent $event, RepairJobOffer $offer, RepairIssue $repair): string
    {
        $actor = $event->relationLoaded('actor') ? $event->actor : null;
        if ($actor && (int) $actor->id === (int) $repair->tenant_id) {
            return 'You';
        }
        if ($actor && (int) $actor->id === (int) $offer->contractor_user_id) {
            return $this->contractorName($offer);
        }
        if ($actor && $repair->created_by && (int) $actor->id === (int) $repair->created_by && $repair->reportedByLandlord()) {
            return 'Landlord';
        }
        if ($actor instanceof User && $offer->invited_by && (int) $actor->id === (int) $offer->invited_by) {
            return 'Landlord';
        }

        return 'ResiSquare';
    }

    private function contractorName(RepairJobOffer $offer): string
    {
        $profile = $offer->contractor?->contractorProfile;
        if ($profile instanceof ContractorProfile) {
            return $profile->displayName();
        }

        $name = trim((string) ($offer->contractor?->first_name ?: $offer->contractor?->name));

        return $name !== '' ? $name : 'Contractor';
    }
}
