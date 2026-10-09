<?php

namespace App\Services\Contractors;

use App\Models\ContractorCoverageGap;
use App\Models\ContractorProfile;
use App\Models\PostcodeLocation;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use App\Support\PostcodeGeo;
use Illuminate\Support\Collection;

class RepairMarketplacePanel
{
    public function __construct(
        private readonly RepairJobOfferService $offers,
        private readonly ContractorDirectory $directory,
        private readonly ContractorMail $mail,
    ) {
    }

    /**
     * Summary for the repair page: request, offers sorted for comparison, award.
     *
     * @return array<string, mixed>
     */
    public function for(RepairIssue $repair, string $sort = 'price'): array
    {
        $this->offers->expireStale($repair);
        $trade = ContractorCompliance::requiredTrade($repair);
        $offers = $repair->jobOffers()->with(['contractor.contractorProfile.documents', 'contractor.contractorProfile.tradeApprovals', 'messages', 'variations', 'invoice'])->latest('id')->get();

        foreach ($offers as $offer) {
            $profile = $offer->contractor?->contractorProfile;
            if ($profile && in_array($offer->status, RepairJobOffer::OPEN_STATUSES, true) && ($reason = ContractorCompliance::blockReason($profile, $trade))) {
                $this->offers->flagCompliance($offer, $reason);
            }
        }

        $live = $offers->filter(fn (RepairJobOffer $offer) => in_array($offer->status, [RepairJobOffer::OFFER_SENT, RepairJobOffer::OFFER_EXPIRED, RepairJobOffer::INSPECTION_ACCEPTED], true));
        $live = match ($sort) {
            'soonest' => $live->sortBy(fn (RepairJobOffer $offer) => $offer->earliestSlot()?->timestamp ?? PHP_INT_MAX),
            'newest' => $live->sortByDesc('offer_sent_at'),
            default => $live->sortBy(fn (RepairJobOffer $offer) => (float) $offer->gross_amount),
        };
        $performance = $this->directory->performance($offers->pluck('contractor_user_id')->unique()->values()->all());

        return [
            'request' => $repair->contractorRequest,
            'offers' => $offers,
            'compare' => $live->values(),
            'waiting' => $offers->filter(fn (RepairJobOffer $offer) => $offer->isAwaitingResponse())->values(),
            'past' => $offers->reject(fn (RepairJobOffer $offer) => $offer->isAwaitingResponse() || $live->contains('id', $offer->id) || $offer->status === RepairJobOffer::JOB_ACCEPTED)->values(),
            'awarded' => $offers->firstWhere('id', $repair->awarded_offer_id),
            'trade' => $trade,
            'performance' => $performance,
            'sort' => $sort,
        ];
    }

    /**
     * Contractors a landlord can send this repair to.
     *
     * @param  array<string, mixed>  $filters
     * @return array{candidates: Collection, trade: ?string, required: ?string, origin: ?array, emptyReason: ?string}
     */
    public function find(RepairIssue $repair, int $accountId, array $filters = []): array
    {
        $required = ContractorCompliance::requiredTrade($repair);
        $trade = $required ?: (array_key_exists($filters['trade'] ?? '', ContractorCompliance::trades()) ? $filters['trade'] : ContractorCompliance::suggestedTrade($repair));
        $postcode = $repair->property?->postcode;
        $origin = PostcodeGeo::locate($postcode);
        $area = PostcodeGeo::area($postcode);
        $maxMiles = is_numeric($filters['distance'] ?? null) ? (float) $filters['distance'] : null;
        $badges = array_values(array_intersect((array) ($filters['badges'] ?? []), ['gas_safe', 'dbs']));
        $only = in_array($filters['only'] ?? null, ['favourite', 'worked', 'mine'], true) ? $filters['only'] : null;
        $relations = $this->directory->relationships($accountId);
        $busy = $repair->jobOffers()->whereIn('status', RepairJobOffer::BLOCKING_STATUSES)->pluck('contractor_user_id')->flip();

        $profiles = ContractorProfile::query()
            ->with(['user', 'documents', 'tradeApprovals'])
            ->where('approval_status', ContractorProfile::APPROVED)
            ->whereNull('removed_at')
            ->get()
            ->filter(fn (ContractorProfile $profile) => ContractorCompliance::canReceiveWork($profile, $trade));
        $lookup = $profiles
            ->filter(fn (ContractorProfile $profile) => $profile->latitude === null && filled($profile->postcode))
            ->map(fn (ContractorProfile $profile) => PostcodeGeo::normalise($profile->postcode))
            ->unique()
            ->values();
        $known = $lookup->isEmpty()
            ? collect()
            : PostcodeLocation::query()->whereIn('postcode', $lookup)->get()->keyBy('postcode');

        $candidates = $profiles
            ->map(function (ContractorProfile $profile) use ($origin, $area, $relations, $known) {
                $point = ($profile->latitude !== null && $profile->longitude !== null)
                    ? [(float) $profile->latitude, (float) $profile->longitude]
                    : (($row = $known->get(PostcodeGeo::normalise($profile->postcode))) ? [(float) $row->latitude, (float) $row->longitude] : null);
                $miles = ($origin && $point) ? PostcodeGeo::miles($origin, $point) : null;
                $radius = (int) ($profile->coverage_radius_miles ?: 15);
                $inArea = $miles !== null ? $miles <= $radius : ($area !== '' && PostcodeGeo::area($profile->postcode) === $area);
                $id = (int) $profile->user_id;

                return (object) [
                    'profile' => $profile,
                    'user_id' => $id,
                    'name' => $profile->displayName(),
                    'miles' => $miles,
                    'in_area' => $inArea,
                    'badges' => ContractorCompliance::badges($profile),
                    'mine' => isset($relations['mine'][$id]),
                    'favourite' => isset($relations['favourite'][$id]),
                    'worked' => isset($relations['worked'][$id]),
                ];
            })
            ->filter(fn ($row) => $row->in_area || $row->mine)
            ->filter(fn ($row) => $maxMiles === null || ($row->miles !== null && $row->miles <= $maxMiles))
            ->filter(fn ($row) => collect($badges)->every(fn ($badge) => ContractorCompliance::hasBadge($row->profile, $badge)))
            ->filter(fn ($row) => $only === null || $row->{$only})
            ->values();
        $alreadyInvited = $candidates->contains(fn ($row) => $busy->has($row->user_id));
        $candidates = $candidates
            ->reject(fn ($row) => $busy->has($row->user_id))
            ->sortBy(fn ($row) => [($row->mine || $row->favourite) ? 0 : 1, $row->worked ? 0 : 1, $row->miles ?? 999])
            ->values();
        $stats = $this->directory->performance($candidates->pluck('user_id')->all());
        $candidates->each(function ($row) use ($stats) {
            $row->rating = $stats[$row->user_id]['rating'] ?? null;
            $row->ratings = $stats[$row->user_id]['ratings'] ?? 0;
        });

        $filtered = ($filters['distance'] ?? null) || $badges || $only;
        $emptyReason = null;
        $gap = false;
        if ($candidates->isEmpty()) {
            $label = strtolower(ContractorCompliance::trades()[$trade] ?? 'this trade');
            if ($filtered) {
                $emptyReason = 'No contractors match these filters. Try clearing them.';
            } elseif ($alreadyInvited) {
                $emptyReason = 'Everyone who covers this repair has already been invited.';
            } else {
                $emptyReason = 'No approved '.$label.' contractors cover '.($area ?: 'this area').' yet.';
                $gap = true;
            }
        }

        return [
            'candidates' => $candidates,
            'trade' => $trade,
            'required' => $required,
            'origin' => $origin,
            'emptyReason' => $emptyReason,
            'gap' => $gap,
            'cap' => ContractorSettings::maxPerSend(),
            'emergency' => ContractorCompliance::looksLikeEmergency($repair),
        ];
    }

    /** Records a coverage gap once per repair and trade, and tells admins. */
    public function recordGap(RepairIssue $repair, ?string $trade): void
    {
        $district = ContractorCompliance::postcodeDistrict($repair->property?->postcode);
        $gap = ContractorCoverageGap::query()->firstOrCreate(
            ['repair_issue_id' => $repair->id, 'trade' => $trade],
            ['account_id' => $repair->account_id, 'postcode_district' => $district]
        );
        if ($gap->wasRecentlyCreated) {
            $this->mail->toAdmins(
                'No contractors for '.($trade ?: 'a repair').' in '.($district ?: 'an area'),
                '<p>A landlord found no approved '.e((string) (ContractorCompliance::trades()[$trade] ?? $trade)).' contractors covering '.e((string) $district).'.</p><p><a href="'.e(route('admin.registrations.contractors.coverage')).'">Open coverage gaps</a></p>',
                'coverage-gap-'.$district.'-'.$trade
            );
        }
    }
}
