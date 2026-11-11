<?php

namespace App\Services\Messaging;

use App\Models\Account;
use App\Models\AccountContractor;
use App\Models\AccountUser;
use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\Messaging\Party;

/**
 * Finds who someone may start a conversation with from a page they are
 * already allowed to see. Every lookup is scoped to the acting party, so an
 * id from another account resolves to nothing (404).
 */
class MessagingDirectory
{
    public function __construct(private readonly RelationshipChecker $relationships) {}

    /** The party behind an email address, or null. Never used to show anything to the sender. */
    public function partyForEmail(string $email): ?Party
    {
        $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
        if (! $user || $user->isSuperAdmin()) {
            return null;
        }
        if ($user->hasRole('Contractor')) {
            return Party::user($user->id);
        }
        $owned = Account::query()->where('owner_user_id', $user->id)->orderBy('id')->value('id');
        if ($owned) {
            return Party::account((int) $owned);
        }
        $operator = AccountUser::query()->where('user_id', $user->id)->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_OPERATOR_TYPES)->orderBy('id')->value('account_id');
        if ($operator) {
            return Party::account((int) $operator);
        }
        $portal = AccountUser::query()->where('user_id', $user->id)->where('status', 'active')->where('can_login', true)
            ->whereIn('member_type', AccountMembership::PORTAL_TYPES)->exists();

        return $portal ? Party::user($user->id) : null;
    }

    /**
     * A context the acting party is allowed to start a conversation from.
     *
     * @return array{party: Party, origin: string, origin_id: int}
     */
    public function relationshipTarget(Party $as, string $context, int $id, ?int $userId = null): array
    {
        $target = match ($context) {
            'tenant' => $this->tenantMember($as, $id),
            'tenancy' => $this->tenancy($as, $id),
            'repair-job' => $this->repairJob($as, $id),
            'compliance-job' => $this->complianceJob($as, $id),
            'event' => $this->event($as, $id, $userId),
            default => null,
        };
        abort_unless($target !== null, 404);

        return $target;
    }

    /** A contractor (by user id) on the account's own list, for a normal request (no job together yet). */
    public function accountContractor(Party $as, int $contractorUserId): Party
    {
        abort_unless($as->isAccount(), 404);
        $link = AccountContractor::query()->where('account_id', $as->id)->where('contractor_user_id', $contractorUserId)->first();
        abort_unless($link !== null, 404);

        return Party::user((int) $link->contractor_user_id);
    }

    private function tenantMember(Party $as, int $memberId): ?array
    {
        if (! $as->isAccount()) {
            return null;
        }
        $member = TenantMember::query()->where('account_id', $as->id)->whereKey($memberId)->first();
        if (! $member || ! $member->user_id) {
            return null;
        }
        $person = Party::user((int) $member->user_id);
        if (! $this->relationships->tenancyLive((int) $member->tenancy_id, $as, $person)) {
            return null;
        }

        return ['party' => $person, 'origin' => Conversation::ORIGIN_TENANCY, 'origin_id' => (int) $member->tenancy_id];
    }

    private function tenancy(Party $as, int $tenancyId): ?array
    {
        if ($as->isAccount()) {
            return null;
        }
        $tenancy = Tenancy::query()->find($tenancyId);
        if (! $tenancy) {
            return null;
        }
        $account = Party::account((int) $tenancy->account_id);
        if (! $this->relationships->tenancyLive($tenancy->id, $account, $as)) {
            return null;
        }

        return ['party' => $account, 'origin' => Conversation::ORIGIN_TENANCY, 'origin_id' => $tenancy->id];
    }

    private function repairJob(Party $as, int $offerId): ?array
    {
        $offer = RepairJobOffer::query()->find($offerId);
        if (! $offer) {
            return null;
        }
        $account = Party::account((int) $offer->account_id);
        $contractor = Party::user((int) $offer->contractor_user_id);
        if (! ($as->is($account) || $as->is($contractor)) || ! $this->relationships->repairLive($offer->id, $account, $contractor)) {
            return null;
        }

        return ['party' => $as->is($account) ? $contractor : $account, 'origin' => Conversation::ORIGIN_REPAIR_JOB, 'origin_id' => $offer->id];
    }

    private function complianceJob(Party $as, int $offerId): ?array
    {
        $offer = ComplianceJobOffer::query()->find($offerId);
        if (! $offer) {
            return null;
        }
        $account = Party::account((int) $offer->account_id);
        $contractor = Party::user((int) $offer->contractor_user_id);
        if (! ($as->is($account) || $as->is($contractor)) || ! $this->relationships->complianceLive($offer->id, $account, $contractor)) {
            return null;
        }

        return ['party' => $as->is($account) ? $contractor : $account, 'origin' => Conversation::ORIGIN_COMPLIANCE_JOB, 'origin_id' => $offer->id];
    }

    private function event(Party $as, int $eventId, ?int $userId): ?array
    {
        $event = Event::query()->find($eventId);
        if (! $event || ! $event->account_id) {
            return null;
        }
        $account = Party::account((int) $event->account_id);
        if ($as->isAccount()) {
            if (! $as->is($account) || ! $userId) {
                return null;
            }
            $person = Party::user($userId);
        } else {
            $person = $as;
        }
        if (! $this->relationships->eventLive($event->id, $account, $person)) {
            return null;
        }

        return ['party' => $as->isAccount() ? $person : $account, 'origin' => Conversation::ORIGIN_EVENT, 'origin_id' => $event->id];
    }
}
