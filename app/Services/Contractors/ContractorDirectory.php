<?php

namespace App\Services\Contractors;

use App\Models\AccountContractor;
use App\Models\ContractorProfile;
use App\Models\RepairJobOffer;
use App\Models\RepairJobOfferEvent;
use App\Models\User;
use App\Services\Portal\ContractorInviteMailer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContractorDirectory
{
    public function __construct(private readonly ContractorMail $mail)
    {
    }

    /** Links a newly signed-up contractor to every landlord who invited that email. */
    public function claimInvites(User $user, ?string $token = null): void
    {
        AccountContractor::query()
            ->whereNull('contractor_user_id')
            ->where(function ($query) use ($user, $token) {
                $query->where('email', strtolower((string) $user->email));
                if ($token) {
                    $query->orWhere('token', $token);
                }
            })
            ->get()
            ->each(function (AccountContractor $link) use ($user) {
                $exists = AccountContractor::query()
                    ->where('account_id', $link->account_id)
                    ->where('contractor_user_id', $user->id)
                    ->exists();
                if ($exists) {
                    $link->delete();

                    return;
                }
                $link->forceFill(['contractor_user_id' => $user->id, 'claimed_at' => now(), 'token' => null])->save();
            });
    }

    /**
     * @return array{status: string, message: string}
     */
    public function invite(int $accountId, string $email, ?string $name, User $landlord): array
    {
        $email = strtolower(trim($email));
        $existing = User::query()->where('email', $email)->first();
        if ($existing && ! $existing->hasRole('Contractor')) {
            return ['status' => 'error', 'message' => 'That email belongs to an account that is not a contractor.'];
        }

        if ($existing) {
            $link = AccountContractor::query()->firstOrNew(['account_id' => $accountId, 'contractor_user_id' => $existing->id]);
            $link->fill(['invited' => true, 'invited_by' => $landlord->id, 'invited_at' => $link->invited_at ?: now(), 'claimed_at' => $link->claimed_at ?: now(), 'email' => $email]);
            $link->save();
            $mail = app(ContractorInviteMailer::class)->sendExisting($existing, $landlord, $accountId);
            if (! $mail['sent']) {
                return ['status' => 'warning', 'message' => 'They are tagged as your contractor, but the email could not be sent.'];
            }

            return ['status' => 'success', 'message' => 'Added. We emailed them to sign in.'];
        }

        $link = AccountContractor::query()->firstOrNew(['account_id' => $accountId, 'email' => $email, 'contractor_user_id' => null]);
        $link->fill([
            'name' => $name,
            'invited' => true,
            'invited_by' => $landlord->id,
            'invited_at' => now(),
            'token' => $link->token ?: Str::random(48),
        ])->save();

        $mail = app(ContractorInviteMailer::class)->sendSignup($email, $name, $landlord, $link);
        if (! $mail['sent']) {
            return ['status' => 'warning', 'message' => 'Invitation saved for '.$email.', but the signup email could not be sent.'];
        }

        return ['status' => 'success', 'message' => 'Invitation sent to '.$email.'. We emailed them a link to sign up.'];
    }

    /**
     * Contractors from the old module get one "claim your new account" email each.
     * Their old assignments and ledger links are untouched.
     */
    public function sendLegacyClaims(User $admin): int
    {
        $sent = 0;
        User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'Contractor'))
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'Contractor'));
            })
            ->whereNotNull('email')
            ->where(function ($query) {
                $query->whereDoesntHave('contractorProfile')
                    ->orWhereHas('contractorProfile', fn ($profile) => $profile->where('legacy_contractor', true)->whereNull('claim_invited_at')->whereNull('terms_accepted_at'));
            })
            ->chunkById(100, function ($users) use ($admin, &$sent) {
                foreach ($users as $user) {
                    $profile = ContractorProfile::query()->firstOrCreate(
                        ['user_id' => $user->id],
                        ['approval_status' => ContractorProfile::DRAFT, 'legacy_contractor' => true, 'phone' => $user->phone, 'postcode' => $user->postcode]
                    );
                    if (! $profile->legacy_contractor || $profile->claim_invited_at || $profile->terms_accepted_at) {
                        continue;
                    }
                    $plain = Str::random(48);
                    $profile->forceFill([
                        'claim_invited_at' => now(),
                        'claim_token_hash' => hash('sha256', $plain),
                        'claim_expires_at' => now()->addHours(72),
                    ])->save();
                    $profile->log('claim_invited', $admin);
                    $link = route('contractor.claim', ['user' => $user->id, 'token' => $plain]);
                    $this->mail->toUser(
                        $user,
                        'Claim your new '.config('app.name').' contractor account',
                        '<p>Hi '.e($user->first_name ?: $user->name ?: 'there').',</p><p>Landlords now send repairs through our new contractor marketplace. It is free. Claim your account, add your insurance and registrations, and once approved you can quote for jobs in your area.</p>'
                            .'<p><a href="'.e($link).'">Claim my account</a></p><p>This link works once and expires in 72 hours.</p>'
                    );
                    $sent++;
                }
            });

        return $sent;
    }

    public function toggleFavourite(int $accountId, int $contractorUserId): bool
    {
        $link = AccountContractor::query()->firstOrNew(['account_id' => $accountId, 'contractor_user_id' => $contractorUserId]);
        $link->favourite = ! $link->favourite;
        $link->save();

        return $link->favourite;
    }

    /**
     * @return array{mine: array<int, bool>, favourite: array<int, bool>, worked: array<int, bool>}
     */
    public function relationships(int $accountId): array
    {
        $links = AccountContractor::query()->where('account_id', $accountId)->whereNotNull('contractor_user_id')->get();
        $worked = RepairJobOffer::query()
            ->where('account_id', $accountId)
            ->where('status', RepairJobOffer::JOB_ACCEPTED)
            ->pluck('contractor_user_id')
            ->unique();

        return [
            'mine' => $links->where('invited', true)->pluck('contractor_user_id')->flip()->map(fn () => true)->all(),
            'favourite' => $links->where('favourite', true)->pluck('contractor_user_id')->flip()->map(fn () => true)->all(),
            'worked' => $worked->flip()->map(fn () => true)->all(),
        ];
    }

    /**
     * Response rate, cancellations and landlord ratings per contractor.
     *
     * @param  array<int, int>  $userIds
     * @return Collection<int, array{invites: int, responded: int, rate: ?int, cancellations: int, awarded: int, rating: ?float, ratings: int}>
     */
    public function performance(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }
        $offers = RepairJobOffer::query()
            ->whereIn('contractor_user_id', $userIds)
            ->get(['id', 'contractor_user_id', 'status', 'offer_sent_at', 'rating']);
        $cancellations = RepairJobOfferEvent::query()
            ->join('repair_job_offers', 'repair_job_offers.id', '=', 'repair_job_offer_events.repair_job_offer_id')
            ->whereIn('repair_job_offers.contractor_user_id', $userIds)
            ->whereColumn('repair_job_offer_events.actor_user_id', 'repair_job_offers.contractor_user_id')
            ->where('repair_job_offer_events.to_status', RepairJobOffer::CANCELLED)
            ->selectRaw('repair_job_offers.contractor_user_id as uid, count(*) as total')
            ->groupBy('repair_job_offers.contractor_user_id')
            ->pluck('total', 'uid');

        return collect($userIds)->mapWithKeys(function (int $id) use ($offers, $cancellations) {
            $mine = $offers->where('contractor_user_id', $id);
            $invites = $mine->count();
            $responded = $mine->filter(fn ($offer) => $offer->offer_sent_at || $offer->status === RepairJobOffer::DECLINED)->count();
            $rated = $mine->whereNotNull('rating');

            return [$id => [
                'rating' => $rated->isNotEmpty() ? round((float) $rated->avg('rating'), 1) : null,
                'ratings' => $rated->count(),
                'invites' => $invites,
                'responded' => $responded,
                'rate' => $invites ? (int) round($responded / $invites * 100) : null,
                'cancellations' => (int) ($cancellations[$id] ?? 0),
                'awarded' => $mine->where('status', RepairJobOffer::JOB_ACCEPTED)->count(),
            ]];
        });
    }
}
