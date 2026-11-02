<?php

namespace App\Services\Contractors;

use App\Models\ComplianceBooking;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceType;
use App\Models\ContractorProfile;
use App\Models\Property;
use App\Models\User;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComplianceBookingService
{
    public function __construct(
        private readonly ContractorEligibility $eligibility,
        private readonly ContractorDirectory $directory,
        private readonly RepairJobOfferService $prices,
        private readonly ContractorMail $mail,
    ) {
    }

    public function open(int $accountId, Property $property, ComplianceType $type): ComplianceBooking
    {
        $trade = ComplianceBooking::tradeForAlias((string) $type->alias);
        if (! $trade) {
            throw new ContractorMarketplaceException('This certificate cannot be booked.');
        }

        $existing = ComplianceBooking::query()
            ->where('account_id', $accountId)
            ->where('property_id', $property->id)
            ->where('compliance_type_id', $type->id)
            ->whereIn('status', ComplianceBooking::OPEN)
            ->latest('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        return ComplianceBooking::query()->create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
            'trade' => $trade,
            'status' => ComplianceBooking::DRAFT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{candidates: Collection, trade: string, required: string, emptyReason: ?string, cap: int, openCount: int}
     */
    public function find(ComplianceBooking $booking, int $accountId, array $filters = []): array
    {
        $this->expire($booking);
        $booking->loadMissing('property', 'type');
        $trade = $booking->trade;
        $postcode = $booking->property?->postcode;
        $maxMiles = is_numeric($filters['distance'] ?? null) ? (float) $filters['distance'] : null;
        $badges = array_values(array_intersect((array) ($filters['badges'] ?? []), ['gas_safe', 'dbs']));
        $only = in_array($filters['only'] ?? null, ['favourite', 'worked', 'mine'], true) ? $filters['only'] : null;
        $relations = $this->directory->relationships($accountId);
        ComplianceJobOffer::query()
            ->where('account_id', $accountId)
            ->where('status', ComplianceJobOffer::JOB_ACCEPTED)
            ->pluck('contractor_user_id')
            ->each(function ($id) use (&$relations) {
                $relations['worked'][(int) $id] = true;
            });
        $busy = $booking->offers()->whereIn('status', ComplianceJobOffer::BLOCKING)->pluck('contractor_user_id')->flip();

        $profiles = ContractorProfile::query()
            ->with(['user', 'documents', 'tradeApprovals'])
            ->where('approval_status', ContractorProfile::APPROVED)
            ->whereNull('removed_at')
            ->get();

        $candidates = $profiles
            ->map(function (ContractorProfile $profile) use ($postcode, $trade, $relations, $busy) {
                $decision = $this->eligibility->assessPostcode($profile, $postcode, $trade);
                $id = (int) $profile->user_id;
                [$scope, $tone, $canTake] = $this->scope($decision, $busy->has($id));

                return (object) [
                    'profile' => $profile,
                    'user_id' => $id,
                    'name' => $profile->displayName(),
                    'miles' => $decision['miles'],
                    'reason' => $decision['reason'],
                    'scope' => $scope,
                    'tone' => $tone,
                    'can_take' => $canTake,
                    'badges' => ContractorCompliance::badges($profile),
                    'mine' => isset($relations['mine'][$id]),
                    'favourite' => isset($relations['favourite'][$id]),
                    'worked' => isset($relations['worked'][$id]),
                    'marketplace' => $profile->onMarketplace(),
                ];
            })
            ->filter(fn ($row) => $row->marketplace || $row->mine)
            ->filter(fn ($row) => $maxMiles === null || ($row->miles !== null && $row->miles <= $maxMiles))
            ->filter(fn ($row) => collect($badges)->every(fn ($badge) => ContractorCompliance::hasBadge($row->profile, $badge)))
            ->filter(fn ($row) => $only === null || $row->{$only})
            ->sortBy(fn ($row) => [$row->can_take ? 0 : 1, ($row->mine || $row->favourite) ? 0 : 1, $row->miles ?? 999])
            ->values();

        $filtered = $maxMiles !== null || $badges !== [] || $only;
        $ready = $candidates->where('can_take', true)->count();
        $emptyReason = null;
        if ($candidates->isEmpty()) {
            $emptyReason = $filtered
                ? 'No contractors match these filters. Try clearing them.'
                : 'No marketplace contractors can take this check. Invite your own.';
        } elseif ($ready === 0) {
            $emptyReason = 'Nobody in this list can take this check. The reason is on each row.';
        }

        return [
            'candidates' => $candidates,
            'trade' => $trade,
            'required' => $trade,
            'emptyReason' => $emptyReason,
            'cap' => ContractorSettings::maxPerSend(),
            'openCount' => $busy->count(),
        ];
    }

    /**
     * @param  array<int, int>  $contractorIds
     * @param  array<string, mixed>  $options
     * @return array{sent: array<int, string>, skipped: array<int, string>}
     */
    public function send(ComplianceBooking $booking, array $contractorIds, User $landlord, array $options = []): array
    {
        if ($booking->status === ComplianceBooking::BOOKED) {
            throw new ContractorMarketplaceException('This check is already booked.');
        }
        if ($booking->status === ComplianceBooking::CANCELLED) {
            throw new ContractorMarketplaceException('This check has been cancelled.');
        }

        $ids = collect($contractorIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            throw new ContractorMarketplaceException('Pick at least one contractor.');
        }

        $booking->loadMissing('property', 'type');
        $already = $booking->offers()->whereIn('status', ComplianceJobOffer::BLOCKING)->pluck('contractor_user_id');
        $fresh = $ids->diff($already->map(fn ($id) => (int) $id));
        $cap = ContractorSettings::maxPerSend();
        if ($already->count() + $fresh->count() > $cap) {
            throw new ContractorMarketplaceException('You can send this check to '.$cap.' contractors at a time.');
        }

        $respondBy = filled($options['respond_by'] ?? null)
            ? \Illuminate\Support\Carbon::parse($options['respond_by'])
            : now()->addHours(ContractorSettings::inviteHours('routine'));
        $sent = [];
        $skipped = [];

        DB::transaction(function () use ($booking, $ids, $landlord, $options, $respondBy, &$sent, &$skipped) {
            foreach ($ids as $userId) {
                $user = User::query()->find($userId);
                $profile = $user?->contractorProfile;
                $name = $profile?->displayName() ?: ($user?->name ?: 'Contractor');
                if (! $user || ! $profile) {
                    $skipped[] = $name.': not a contractor.';
                    continue;
                }
                if (! $this->directory->isListedFor($profile, (int) $booking->account_id)) {
                    $skipped[] = $name.': they are not available to this account.';
                    continue;
                }
                $decision = $this->eligibility->assessPostcode($profile, $booking->property?->postcode, $booking->trade);
                if (! $decision['ok']) {
                    $skipped[] = $name.': '.$decision['reason'];
                    continue;
                }

                $offer = ComplianceJobOffer::query()->firstOrNew([
                    'compliance_booking_id' => $booking->id,
                    'contractor_user_id' => $user->id,
                ]);
                if (in_array($offer->status, ComplianceJobOffer::BLOCKING, true)) {
                    $skipped[] = $name.': already invited.';
                    continue;
                }

                $offer->fill([
                    'account_id' => $booking->account_id,
                    'invited_by' => $landlord->id,
                    'status' => ComplianceJobOffer::INVITED,
                    'invited_at' => now(),
                    'invite_expires_at' => $respondBy,
                    'viewed_at' => null,
                    'offer_sent_at' => null,
                    'offer_expires_at' => null,
                    'decided_at' => null,
                    'decline_reason' => null,
                    'decline_code' => null,
                ])->save();
                $sent[] = $name;

                $check = $booking->type?->name ?: 'Compliance check';
                $area = ContractorCompliance::postcodeDistrict($booking->property?->postcode);
                $url = route('contractor.jobs.compliance.show', $offer);
                $this->mail->toUser(
                    $user,
                    $check.' to quote',
                    '<p>A landlord wants a quote for '.e($check).' in '.e($area ?: 'their area').'.</p><p><a href="'.e($url).'">Open the job</a></p>'
                );
                $this->mail->bell($user, $check, 'A landlord wants a quote for '.$check.'.', $url, (int) $booking->account_id, 'compliance.booking');
            }

            if ($sent !== []) {
                $booking->forceFill([
                    'status' => ComplianceBooking::SENT,
                    'access_notes' => $options['access_notes'] ?? $booking->access_notes,
                    'respond_by' => $respondBy,
                    'sent_by' => $landlord->id,
                    'first_sent_at' => $booking->first_sent_at ?: now(),
                    'last_sent_at' => now(),
                ])->save();
            }
        });

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    public function markViewed(ComplianceJobOffer $offer): void
    {
        if ($offer->status === ComplianceJobOffer::INVITED) {
            $offer->forceFill([
                'status' => ComplianceJobOffer::VIEWED,
                'viewed_at' => now(),
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function quote(ComplianceJobOffer $offer, User $contractor, array $input): void
    {
        $this->expire($offer->booking);
        $offer->refresh();
        if ((int) $offer->contractor_user_id !== (int) $contractor->id) {
            throw new ContractorMarketplaceException('This job is not yours.');
        }
        if (! $offer->canQuote()) {
            throw new ContractorMarketplaceException('This invitation is no longer open.');
        }
        if (($input['pricing_type'] ?? 'fixed') === 'inspection') {
            throw new ContractorMarketplaceException('Send a firm price for this check.');
        }
        $profile = $contractor->contractorProfile;
        if (! $profile) {
            throw new ContractorMarketplaceException('Complete your profile first.');
        }
        $booking = $offer->booking()->with('property', 'type')->first();
        $block = ContractorCompliance::blockReason($profile, $booking?->trade);
        if ($block) {
            throw new ContractorMarketplaceException($block);
        }

        $slots = collect($input['visit_slots'] ?? [])
            ->filter()
            ->map(fn ($slot) => \Illuminate\Support\Carbon::parse($slot))
            ->filter(fn ($slot) => $slot->isFuture())
            ->values();
        if ($slots->isEmpty()) {
            throw new ContractorMarketplaceException('Add a visit time in the future.');
        }

        $priced = $this->prices->price($profile, $input + ['materials' => $input['materials'] ?? 'included']);
        $revision = $offer->status === ComplianceJobOffer::OFFER_SENT ? $offer->revision + 1 : $offer->revision;

        $offer->fill($priced + [
            'status' => ComplianceJobOffer::OFFER_SENT,
            'revision' => $revision,
            'visit_slots' => $slots->map(fn ($slot) => $slot->toIso8601String())->all(),
            'offer_sent_at' => now(),
            'offer_expires_at' => now()->addHours(ContractorSettings::offerValidHours('routine')),
        ])->save();

        $landlord = User::query()->find($offer->invited_by);
        $check = $booking?->type?->name ?: 'Compliance check';
        $url = $booking ? route('admin.compliance.bookings.show', $booking) : null;
        $this->mail->toUser($landlord, 'Quote for '.$check, '<p>'.e($offer->contractorName()).' quoted '.e($offer->priceSummary()).'.</p>'.($url ? '<p><a href="'.e($url).'">Review the quote</a></p>' : ''));
        $this->mail->bell($landlord, 'Quote for '.$check, $offer->contractorName().' quoted '.$offer->priceSummary().'.', $url, (int) $offer->account_id, 'compliance.booking');
    }

    public function decline(ComplianceJobOffer $offer, User $contractor, ?string $code, ?string $reason): void
    {
        if ((int) $offer->contractor_user_id !== (int) $contractor->id || ! $offer->isAwaitingResponse()) {
            throw new ContractorMarketplaceException('This invitation is no longer open.');
        }
        $offer->forceFill([
            'status' => ComplianceJobOffer::DECLINED,
            'decline_code' => $code,
            'decline_reason' => $reason,
            'decided_at' => now(),
        ])->save();
    }

    public function accept(ComplianceJobOffer $offer, User $landlord, int $expectedRevision): void
    {
        $booking = $offer->booking()->with('property', 'type')->first();
        if (! $booking || (int) $booking->account_id !== (int) current_account_id()) {
            throw new ContractorMarketplaceException('This quote is not on your account.');
        }
        $this->expire($booking);
        $offer->refresh();
        if ($offer->status !== ComplianceJobOffer::OFFER_SENT) {
            throw new ContractorMarketplaceException('This quote is no longer open.');
        }
        if ((int) $offer->revision !== $expectedRevision) {
            throw new ContractorMarketplaceException('This quote changed. Review the new price before you accept.');
        }

        DB::transaction(function () use ($offer, $booking) {
            $when = collect($offer->visit_slots ?? [])->filter()->map(fn ($slot) => \Illuminate\Support\Carbon::parse($slot))->sort()->first();
            $offer->forceFill([
                'status' => ComplianceJobOffer::JOB_ACCEPTED,
                'decided_at' => now(),
                'scheduled_for' => $when,
            ])->save();

            $booking->offers()
                ->where('id', '!=', $offer->id)
                ->whereIn('status', ComplianceJobOffer::OPEN_STATUSES)
                ->update([
                    'status' => ComplianceJobOffer::NOT_SELECTED,
                    'decided_at' => now(),
                ]);

            $booking->forceFill([
                'status' => ComplianceBooking::BOOKED,
                'awarded_offer_id' => $offer->id,
                'awarded_at' => now(),
            ])->save();
        });

        $offer->refresh()->load('contractor.contractorProfile');
        $booking->refresh()->load('property', 'type');
        $check = $booking->type?->name ?: 'Compliance check';
        $address = trim(collect([$booking->property?->line_1, $booking->property?->city, $booking->property?->postcode])->filter()->implode(', '));
        $url = route('contractor.jobs.compliance.show', $offer);
        $this->mail->toUser(
            $offer->contractor,
            'You are booked for '.$check,
            '<p>The landlord accepted your quote for '.e($check).'.</p><p>'.e($address).'</p><p><a href="'.e($url).'">Open the job</a></p>'
        );
        $this->mail->bell($offer->contractor, 'Booked: '.$check, 'The landlord accepted your quote. The address is on the job.', $url, (int) $booking->account_id, 'compliance.booking');
    }

    public function expire(ComplianceBooking $booking): void
    {
        $booking->offers()
            ->whereIn('status', ComplianceJobOffer::AWAITING)
            ->whereNotNull('invite_expires_at')
            ->where('invite_expires_at', '<', now())
            ->update(['status' => ComplianceJobOffer::EXPIRED]);

        $booking->offers()
            ->where('status', ComplianceJobOffer::OFFER_SENT)
            ->whereNotNull('offer_expires_at')
            ->where('offer_expires_at', '<', now())
            ->update(['status' => ComplianceJobOffer::OFFER_EXPIRED]);
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
        if (str_contains($reason, 'Not approved for') || str_contains($reason, 'needs a') || str_contains($reason, 'not approved yet') || str_contains($reason, 'No trade') || str_contains($reason, 'missing') || str_contains($reason, 'Gas Safe') || str_contains($reason, 'electrical')) {
            return ['Outside their trade', 'idle', false];
        }
        if (str_contains($reason, 'distance')) {
            return ['Area unknown', 'idle', false];
        }

        return ['Cannot take this', 'bad', false];
    }
}
