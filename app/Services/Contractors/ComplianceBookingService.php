<?php

namespace App\Services\Contractors;

use App\Models\Account;
use App\Models\ComplianceBooking;
use App\Models\ComplianceJobInvoice;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceJobVariation;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\ContractorProfile;
use App\Models\Document;
use App\Models\Property;
use App\Models\Upload;
use App\Models\User;
use App\Services\SecureUploadService;
use App\Support\ContractorCompliance;
use App\Support\ContractorSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        if ($booking->status === ComplianceBooking::COMPLETED) {
            throw new ContractorMarketplaceException('This check is already complete.');
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
                'job_status' => $when ? ComplianceJobOffer::JOB_SCHEDULED : ComplianceJobOffer::JOB_ACCEPTED_STATE,
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
            '<p>The landlord accepted your quote for '.e($check).'.</p><p>'.e($address).'</p><p><a href="'.e($url).'">Open the job</a>'
            .(config('messaging.enabled', true) ? ' · <a href="'.e(route('contractor.messages.start', ['context' => 'compliance-job', 'id' => $offer->id])).'">Message the landlord</a>' : '')
            .'</p>'
        );
        $this->mail->bell($offer->contractor, 'Booked: '.$check, 'The landlord accepted your quote. The address is on the job.', $url, (int) $booking->account_id, 'compliance.booking');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function progress(ComplianceJobOffer $offer, User $contractor, string $step, array $data = []): void
    {
        if ((int) $offer->contractor_user_id !== (int) $contractor->id) {
            throw new ContractorMarketplaceException('This job is not yours.');
        }

        $booking = $offer->booking()->with('property', 'type')->first();
        if (! $booking || $offer->status !== ComplianceJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('This check is not booked.');
        }
        if ($offer->job_status === ComplianceJobOffer::JOB_CONFIRMED || $booking->status === ComplianceBooking::COMPLETED) {
            throw new ContractorMarketplaceException('The landlord has already confirmed this check.');
        }
        if ($offer->isFinished()) {
            throw new ContractorMarketplaceException('This check is already marked complete.');
        }
        if ($booking->status !== ComplianceBooking::BOOKED) {
            throw new ContractorMarketplaceException('This check is not booked.');
        }

        $storedPath = null;
        try {
            DB::transaction(function () use ($offer, $step, $data, $booking, $contractor, &$storedPath) {
                $locked = ComplianceJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== ComplianceJobOffer::JOB_ACCEPTED || $locked->isFinished()) {
                    throw new ContractorMarketplaceException($locked->job_status === ComplianceJobOffer::JOB_CONFIRMED
                        ? 'The landlord has already confirmed this check.'
                        : 'This check is already marked complete.');
                }

                match ($step) {
                    'schedule' => $this->scheduleVisit($locked, $data),
                    'start' => $this->startVisit($locked),
                    'complete' => $storedPath = $this->completeVisit($locked, $booking, $contractor, $data),
                    default => throw new ContractorMarketplaceException('Unknown step.'),
                };
                $offer->setRawAttributes($locked->getAttributes(), true);
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                app(SecureUploadService::class)->delete($storedPath);
            }
            throw $exception;
        }

        $offer->refresh();
        $booking->refresh();
        $check = $booking->type?->name ?: 'Compliance check';
        $url = route('admin.compliance.bookings.show', $booking);
        $when = $offer->scheduled_for?->format('D j M, H:i');
        $landlord = User::query()->find($offer->invited_by);

        if ($step === 'schedule') {
            $this->mail->toUser($landlord, 'Visit moved: '.$check, '<p>'.e($offer->contractorName()).' moved the visit to '.e((string) $when).'.</p><p><a href="'.e($url).'">Open the booking</a></p>');
            $this->mail->bell($landlord, 'Visit moved: '.$check, $offer->contractorName().' moved the visit to '.$when.'.', $url, (int) $booking->account_id, 'compliance.booking');
        } elseif ($step === 'start') {
            $this->mail->toUser($landlord, 'Work started: '.$check, '<p>'.e($offer->contractorName()).' has started '.e($check).'.</p><p><a href="'.e($url).'">Open the booking</a></p>');
            $this->mail->bell($landlord, 'Work started: '.$check, $offer->contractorName().' has started the check.', $url, (int) $booking->account_id, 'compliance.booking');
        } else {
            $this->mail->toUser($landlord, 'Check complete: '.$check, '<p>'.e($offer->contractorName()).' marked '.e($check).' complete and filed the certificate. Confirm it when you are satisfied.</p><p><a href="'.e($url).'">Open the booking</a></p>');
            $this->mail->bell($landlord, 'Check complete: '.$check, $offer->contractorName().' marked the check complete and filed the certificate. Confirm it on the booking.', $url, (int) $booking->account_id, 'compliance.booking');
        }
    }

    public function confirm(ComplianceJobOffer $offer, User $landlord): void
    {
        $booking = $offer->booking()->with('type')->first();
        if (! $booking || (int) $booking->account_id !== (int) current_account_id()) {
            throw new ContractorMarketplaceException('This check is not on your account.');
        }
        if ((int) $booking->awarded_offer_id !== (int) $offer->id || $offer->status !== ComplianceJobOffer::JOB_ACCEPTED) {
            throw new ContractorMarketplaceException('This contractor is not booked for the check.');
        }

        DB::transaction(function () use ($offer, $booking) {
            $locked = ComplianceJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if ($locked->job_status === ComplianceJobOffer::JOB_CONFIRMED || $booking->status === ComplianceBooking::COMPLETED) {
                throw new ContractorMarketplaceException('This check is already confirmed.');
            }
            if ($locked->job_status !== ComplianceJobOffer::JOB_COMPLETED) {
                throw new ContractorMarketplaceException('The contractor has not marked this check complete yet.');
            }
            $locked->forceFill([
                'job_status' => ComplianceJobOffer::JOB_CONFIRMED,
                'confirmed_at' => now(),
            ])->save();
            $booking->forceFill(['status' => ComplianceBooking::COMPLETED])->save();
            $offer->setRawAttributes($locked->getAttributes(), true);
        });

        $offer->refresh()->load('contractor');
        $booking->refresh()->load('type');
        $check = $booking->type?->name ?: 'Compliance check';
        $url = route('contractor.jobs.compliance.show', $offer);
        $this->mail->toUser(
            $offer->contractor,
            'Check confirmed: '.$check,
            '<p>'.e($landlord->name ?: 'The landlord').' confirmed '.e($check).' is complete.</p><p><a href="'.e($url).'">Open the job</a></p>'
        );
        $this->mail->bell($offer->contractor, 'Check confirmed: '.$check, 'The landlord confirmed the check is complete.', $url, (int) $booking->account_id, 'compliance.booking');
    }

    public function issueInvoice(ComplianceJobOffer $offer, User $contractor, ?string $notes = null): ComplianceJobInvoice
    {
        if ((int) $offer->contractor_user_id !== (int) $contractor->id) {
            throw new ContractorMarketplaceException('This job is not yours.');
        }
        $offer->loadMissing('booking.property', 'booking.type', 'contractor.contractorProfile', 'variations');
        if ($offer->status !== ComplianceJobOffer::JOB_ACCEPTED || $offer->job_status !== ComplianceJobOffer::JOB_CONFIRMED) {
            throw new ContractorMarketplaceException('You can invoice once the landlord confirms the check is done.');
        }
        if ($offer->invoice()->exists()) {
            throw new ContractorMarketplaceException('This check already has an invoice.');
        }
        if ($offer->variations->where('status', ComplianceJobVariation::PENDING)->isNotEmpty()) {
            throw new ContractorMarketplaceException('Wait for the landlord to answer your extra work request first.');
        }
        $profile = $offer->contractor?->contractorProfile;
        if (! $profile?->hasBankDetails()) {
            throw new ContractorMarketplaceException('Add your bank details before you send an invoice.');
        }
        $net = round((float) $offer->net_amount, 2);
        if ($net <= 0) {
            throw new ContractorMarketplaceException('This quote has no price.');
        }

        $rate = (float) ($offer->vat_rate ?? 0);
        $vat = round($net * $rate / 100, 2);
        $gross = round($net + $vat, 2);
        $check = $offer->booking?->type?->name ?: 'Compliance check';
        $work = trim((string) $offer->completion_note);
        $lines = [[
            'description' => $check.($work !== '' ? ' – '.Str::limit($work, 180) : ''),
            'net' => $net,
            'vat_rate' => $rate,
            'vat' => $vat,
            'gross' => $gross,
        ]];
        foreach ($offer->variations->where('status', ComplianceJobVariation::APPROVED) as $variation) {
            $extraNet = round((float) $variation->net_amount, 2);
            $extraRate = (float) $variation->vat_rate;
            $extraVat = round($extraNet * $extraRate / 100, 2);
            $lines[] = [
                'description' => 'Extra work: '.Str::limit($variation->reason, 160),
                'net' => $extraNet,
                'vat_rate' => $extraRate,
                'vat' => $extraVat,
                'gross' => round($extraNet + $extraVat, 2),
            ];
        }
        $net = round((float) collect($lines)->sum('net'), 2);
        $vat = round((float) collect($lines)->sum('vat'), 2);
        $gross = round((float) collect($lines)->sum('gross'), 2);
        $number = 'CC-'.str_pad((string) $offer->id, 6, '0', STR_PAD_LEFT);
        $account = Account::query()->with('owner')->find($offer->account_id);
        $owner = $account?->owner;
        $property = $offer->booking?->property;

        $invoice = DB::transaction(function () use ($offer, $contractor, $lines, $profile, $notes, $number, $account, $owner, $property, $net, $vat, $gross) {
            $locked = ComplianceJobOffer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if ($locked->job_status !== ComplianceJobOffer::JOB_CONFIRMED) {
                throw new ContractorMarketplaceException('You can invoice once the landlord confirms the check is done.');
            }
            if (ComplianceJobInvoice::query()->where('compliance_job_offer_id', $locked->id)->exists()) {
                throw new ContractorMarketplaceException('This check already has an invoice.');
            }
            if (ComplianceJobVariation::query()->where('compliance_job_offer_id', $locked->id)->where('status', ComplianceJobVariation::PENDING)->exists()) {
                throw new ContractorMarketplaceException('Wait for the landlord to answer your extra work request first.');
            }

            $invoice = ComplianceJobInvoice::create([
                'compliance_job_offer_id' => $locked->id,
                'account_id' => $locked->account_id,
                'contractor_user_id' => $contractor->id,
                'number' => $number,
                'issued_on' => today(),
                'due_on' => today()->addDays(max(0, (int) ContractorSettings::get('invoice_due_days'))),
                'supplied_on' => $locked->completed_at?->toDateString(),
                'lines' => $lines,
                'net_amount' => $net,
                'vat_amount' => $vat,
                'gross_amount' => $gross,
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
                'status' => ComplianceJobInvoice::ISSUED,
            ]);
            $locked->forceFill(['job_status' => ComplianceJobOffer::JOB_INVOICED])->save();

            return $invoice;
        });

        $booking = $offer->booking;
        $url = $booking ? route('admin.compliance.bookings.invoice', [$booking, $offer]) : null;
        $landlord = User::query()->find($offer->invited_by);
        $due = $invoice->due_on?->format('j M Y');
        $this->mail->toUser(
            $landlord,
            'Invoice '.$invoice->number,
            '<p>'.e($offer->contractorName()).' sent invoice '.e($invoice->number).' for £'.e(number_format((float) $invoice->gross_amount, 2)).', due '.e((string) $due).'.</p>'.($url ? '<p><a href="'.e($url).'">Open the invoice</a></p>' : '')
        );
        $this->mail->bell($landlord, 'Invoice '.$invoice->number, $offer->contractorName().' sent invoice '.$invoice->number.' for £'.number_format((float) $invoice->gross_amount, 2).', due '.$due.'.', $url, (int) $offer->account_id, 'compliance.booking');

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function scheduleVisit(ComplianceJobOffer $offer, array $data): void
    {
        if (! filled($data['scheduled_for'] ?? null)) {
            throw new ContractorMarketplaceException('Pick a visit time in the future.');
        }
        $when = \Illuminate\Support\Carbon::parse($data['scheduled_for']);
        if ($when->isPast()) {
            throw new ContractorMarketplaceException('Pick a visit time in the future.');
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
    }

    private function startVisit(ComplianceJobOffer $offer): void
    {
        if (! $offer->scheduled_for) {
            throw new ContractorMarketplaceException('Set a visit time first.');
        }
        if ($offer->job_status === ComplianceJobOffer::JOB_IN_PROGRESS) {
            throw new ContractorMarketplaceException('Work has already started.');
        }
        $offer->forceFill([
            'job_status' => ComplianceJobOffer::JOB_IN_PROGRESS,
            'started_at' => $offer->started_at ?: now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function completeVisit(ComplianceJobOffer $offer, ComplianceBooking $booking, User $contractor, array $data): string
    {
        if (! $offer->scheduled_for) {
            throw new ContractorMarketplaceException('Set a visit time first.');
        }
        $file = $data['certificate'] ?? null;
        if (! $file instanceof UploadedFile) {
            throw new ContractorMarketplaceException('Attach the certificate.');
        }
        $issued = \Illuminate\Support\Carbon::parse($data['issued_date'] ?? '');
        $expiry = \Illuminate\Support\Carbon::parse($data['expiry_date'] ?? '');
        if ($expiry->startOfDay()->lt($issued->copy()->startOfDay())) {
            throw new ContractorMarketplaceException('The expiry date must be on or after the issued date.');
        }

        $booking->loadMissing('property', 'type');
        $uploads = app(SecureUploadService::class);
        $stored = $uploads->store($file);
        try {
            $upload = Upload::create([
                'account_id' => $booking->account_id,
                'file_original_name' => pathinfo($stored['original_name'], PATHINFO_FILENAME) ?: ($booking->type?->name ?: 'Certificate'),
                'extension' => $stored['extension'],
                'file_name' => $stored['path'],
                'user_id' => $contractor->id,
                'type' => $stored['type'],
                'file_size' => $stored['size'],
            ]);
            Document::create([
                'account_id' => $booking->account_id,
                'documentable_type' => $booking->property?->getMorphClass() ?: Property::class,
                'documentable_id' => $booking->property_id,
                'upload_ids' => (string) $upload->id,
                'title' => $booking->type?->name ?: 'Certificate',
                'visibility' => 'private',
                'created_by' => $contractor->id,
            ]);
            $record = ComplianceRecord::create([
                'property_id' => $booking->property_id,
                'compliance_type_id' => $booking->compliance_type_id,
                'issued_date' => $issued->toDateString(),
                'expiry_date' => $expiry->toDateString(),
                'photos' => (string) $upload->id,
                'responsible_user_id' => $offer->invited_by,
            ]);
            $note = trim((string) ($data['completion_note'] ?? ''));
            $offer->forceFill([
                'job_status' => ComplianceJobOffer::JOB_COMPLETED,
                'completed_at' => now(),
                'completion_note' => $note !== '' ? $note : null,
                'compliance_record_id' => $record->id,
            ])->save();
        } catch (\Throwable $exception) {
            $uploads->delete($stored['path']);
            throw $exception;
        }

        return $stored['path'];
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
