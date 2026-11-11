<?php

namespace App\Services\Messaging;

use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Support\Messaging\Party;

/**
 * Conversations opened because two parties work together become read-only
 * once that relationship ends. Ones started from an accepted request stay
 * open; any conversation turns read-only when a party can no longer log in.
 */
class RelationshipChecker
{
    public const REPAIR_LIVE = [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::CLOSED];

    public const COMPLIANCE_LIVE = [ComplianceJobOffer::JOB_ACCEPTED];

    /** Null when the conversation may stay open, otherwise the reason it is read-only. */
    public function endedReason(Conversation $conversation): ?string
    {
        $parties = $conversation->participants->map->party();
        foreach ($parties as $party) {
            if (! $party->isActive()) {
                return 'This account is closed, so the conversation is read-only.';
            }
        }

        $account = $parties->first(fn (Party $p) => $p->isAccount());
        $person = $parties->first(fn (Party $p) => ! $p->isAccount());

        return match ($conversation->origin) {
            Conversation::ORIGIN_TENANCY => $this->tenancyLive($conversation->origin_id, $account, $person) ? null : 'The tenancy has ended, so the conversation is read-only.',
            Conversation::ORIGIN_REPAIR_JOB => $this->repairLive($conversation->origin_id, $account, $person) ? null : 'The job has ended, so the conversation is read-only.',
            Conversation::ORIGIN_COMPLIANCE_JOB => $this->complianceLive($conversation->origin_id, $account, $person) ? null : 'The check has ended, so the conversation is read-only.',
            Conversation::ORIGIN_EVENT => $this->eventLive($conversation->origin_id, $account, $person) ? null : 'The appointment has ended, so the conversation is read-only.',
            default => null,
        };
    }

    public function tenancyLive(?int $tenancyId, ?Party $account, ?Party $person): bool
    {
        if (! $tenancyId || ! $account || ! $person) {
            return false;
        }
        $tenancy = Tenancy::query()->find($tenancyId);

        return $tenancy
            && $tenancy->status === 'Active'
            && (int) $tenancy->account_id === $account->id
            && TenantMember::query()->where('tenancy_id', $tenancy->id)->where('user_id', $person->id)->exists();
    }

    public function repairLive(?int $offerId, ?Party $account, ?Party $person): bool
    {
        $offer = $offerId ? RepairJobOffer::query()->find($offerId) : null;

        return $offer && $account && $person
            && (int) $offer->account_id === $account->id
            && (int) $offer->contractor_user_id === $person->id
            && in_array($offer->status, self::REPAIR_LIVE, true);
    }

    public function complianceLive(?int $offerId, ?Party $account, ?Party $person): bool
    {
        $offer = $offerId ? ComplianceJobOffer::query()->find($offerId) : null;

        return $offer && $account && $person
            && (int) $offer->account_id === $account->id
            && (int) $offer->contractor_user_id === $person->id
            && in_array($offer->status, self::COMPLIANCE_LIVE, true);
    }

    public function eventLive(?int $eventId, ?Party $account, ?Party $person): bool
    {
        $event = $eventId ? Event::query()->find($eventId) : null;

        return $event && $account && $person
            && (int) $event->account_id === $account->id
            && $event->users()->where('users.id', $person->id)->exists();
    }
}
