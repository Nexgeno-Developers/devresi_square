<?php

namespace App\Services\Contractors;

use App\Models\ContractorCoverageGap;
use App\Models\ContractorProfile;
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
        private readonly ContractorEligibility $eligibility,
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
        $tradeFilter = array_key_exists($filters['trade'] ?? '', ContractorCompliance::trades()) ? $filters['trade'] : null;
        $trade = $required ?: ContractorCompliance::suggestedTrade($repair);
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
            ->get();

        $candidates = $profiles
            ->map(function (ContractorProfile $profile) use ($repair, $trade, $tradeFilter, $relations, $busy) {
                $decision = $this->eligibility->assess($profile, $repair, $trade);
                $id = (int) $profile->user_id;
                $invited = $busy->has($id);
                [$scope, $tone, $canTake] = $this->scope($decision, $invited);

                return (object) [
                    'profile' => $profile,
                    'user_id' => $id,
                    'name' => $profile->displayName(),
                    'miles' => $decision['miles'],
                    'in_area' => $decision['ok'],
                    'reason' => $decision['reason'],
                    'scope' => $scope,
                    'tone' => $tone,
                    'can_take' => $canTake,
                    'has_trade' => $tradeFilter === null || ContractorCompliance::tradeBlock($profile, $tradeFilter) === null,
                    'badges' => ContractorCompliance::badges($profile),
                    'mine' => isset($relations['mine'][$id]),
                    'favourite' => isset($relations['favourite'][$id]),
                    'worked' => isset($relations['worked'][$id]),
                ];
            })
            ->filter(fn ($row) => $row->has_trade)
            ->filter(fn ($row) => $maxMiles === null || ($row->miles !== null && $row->miles <= $maxMiles))
            ->filter(fn ($row) => collect($badges)->every(fn ($badge) => ContractorCompliance::hasBadge($row->profile, $badge)))
            ->filter(fn ($row) => $only === null || $row->{$only})
            ->sortBy(fn ($row) => [$row->can_take ? 0 : 1, ($row->mine || $row->favourite) ? 0 : 1, $row->miles ?? 999])
            ->values();
        $stats = $this->directory->performance($candidates->pluck('user_id')->all());
        $candidates->each(function ($row) use ($stats) {
            $row->rating = $stats[$row->user_id]['rating'] ?? null;
            $row->ratings = $stats[$row->user_id]['ratings'] ?? 0;
        });

        $filtered = $tradeFilter || $maxMiles !== null || $badges || $only;
        $ready = $candidates->where('can_take', true)->count();
        $emptyReason = null;
        $gap = false;
        if ($candidates->isEmpty()) {
            $emptyReason = $filtered
                ? 'No contractors match these filters. Try clearing them.'
                : 'No approved contractors are on Resisquare yet.';
            $gap = ! $filtered;
        } elseif ($ready === 0) {
            $emptyReason = 'Nobody in this list can take this repair. The reason is on each row.';
        }

        return [
            'candidates' => $candidates,
            'ready' => $ready,
            'trade' => $trade,
            'required' => $required,
            'origin' => $origin,
            'emptyReason' => $emptyReason,
            'omitted' => [],
            'gap' => $gap,
            'cap' => ContractorSettings::maxPerSend(),
            'emergency' => ContractorCompliance::looksLikeEmergency($repair),
            'area' => $area,
        ];
    }

    /**
     * @param  array{ok: bool, miles: ?float, reason: ?string}  $decision
     * @return array{0: string, 1: string, 2: bool}
     */
    private function scope(array $decision, bool $invited): array
    {
        if ($invited) {
            return ['Already invited', 'idle', false];
        }
        if ($decision['ok']) {
            return ['Can take this', 'ok', true];
        }

        $reason = (string) $decision['reason'];
        if (str_contains($reason, 'coverage') || str_contains($reason, 'miles away')) {
            return ['Outside their area', 'warn', false];
        }
        if (str_contains($reason, 'Not approved for') || str_contains($reason, 'needs a') || str_contains($reason, 'not approved yet') || str_contains($reason, 'No trade')) {
            return ['Outside their trade', 'idle', false];
        }
        if (str_contains($reason, 'distance')) {
            return ['Area unknown', 'idle', false];
        }

        return ['Cannot take this', 'bad', false];
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
