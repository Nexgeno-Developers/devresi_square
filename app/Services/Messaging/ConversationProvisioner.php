<?php

namespace App\Services\Messaging;

use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Support\Messaging\Party;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The only code that creates or archives chats. There are exactly two kinds:
 *
 *  - tenancy: the managing account + every tenant on an active tenancy.
 *  - repair_job / compliance_job: the account + the assigned contractor +
 *    the tenants living there, from award until the job is finished.
 *
 * Every method is idempotent, so model hooks, page loads and the daily
 * messaging:sync command can all call it safely.
 */
class ConversationProvisioner
{
    public const TENANCY_LIVE = 'Active';

    /** job_status values that mean the landlord signed the job off. */
    public const JOB_DONE = ['confirmed', 'invoiced'];

    public function __construct(private readonly MessagingNotifier $notifier) {}

    // ── Tenancy chats ───────────────────────────────────────────────────────

    public function syncTenancy(Tenancy $tenancy): ?Conversation
    {
        $conversation = $this->find(Conversation::KIND_TENANCY, $tenancy->id);
        if ($tenancy->status !== self::TENANCY_LIVE || ! $tenancy->account_id) {
            if ($conversation) {
                $this->archive($conversation, 'The tenancy has ended, so this chat is archived.');
            }

            return $conversation;
        }

        $conversation ??= $this->create(Conversation::KIND_TENANCY, $tenancy->id, (int) $tenancy->account_id, $tenancy->property_id);
        $this->reopen($conversation);
        $this->syncParticipants($conversation, [
            [Party::account((int) $tenancy->account_id), ConversationParticipant::LANDLORD],
        ], $this->tenantUserIds([(int) $tenancy->id]));

        return $conversation;
    }

    // ── Job chats ───────────────────────────────────────────────────────────

    public function syncRepairJob(RepairJobOffer $offer): ?Conversation
    {
        $conversation = $this->find(Conversation::KIND_REPAIR_JOB, $offer->id);
        $live = $offer->status === RepairJobOffer::JOB_ACCEPTED && ! in_array($offer->job_status, self::JOB_DONE, true);
        if (! $live) {
            if ($conversation) {
                $this->archive($conversation, $this->jobEndedReason($offer->status === RepairJobOffer::JOB_ACCEPTED || $offer->status === RepairJobOffer::CLOSED));
            }

            return $conversation;
        }

        $repair = $offer->repairIssue()->first();
        $propertyId = $repair?->property_id ? (int) $repair->property_id : null;
        $isNew = $conversation === null;
        $conversation ??= $this->create(Conversation::KIND_REPAIR_JOB, $offer->id, (int) $offer->account_id, $propertyId);
        $this->syncParticipants($conversation, [
            [Party::account((int) $offer->account_id), ConversationParticipant::LANDLORD],
            [Party::user((int) $offer->contractor_user_id), ConversationParticipant::CONTRACTOR],
        ], $this->tenantsAtProperty((int) $offer->account_id, $propertyId, $repair?->tenant_id ? (int) $repair->tenant_id : null));

        if ($isNew) {
            $ref = $repair?->reference_number ?: ('#'.$offer->repair_issue_id);
            $this->opened($conversation, 'Repair '.$ref.' is assigned to '.$this->contractorName($offer->contractor_user_id).'. Use this chat to arrange the visit. It is archived when the job is signed off.');
        }

        return $conversation;
    }

    public function syncComplianceJob(ComplianceJobOffer $offer): ?Conversation
    {
        $conversation = $this->find(Conversation::KIND_COMPLIANCE_JOB, $offer->id);
        $live = $offer->status === ComplianceJobOffer::JOB_ACCEPTED && ! in_array($offer->job_status, self::JOB_DONE, true);
        if (! $live) {
            if ($conversation) {
                $this->archive($conversation, $this->jobEndedReason($offer->status === ComplianceJobOffer::JOB_ACCEPTED));
            }

            return $conversation;
        }

        $booking = $offer->booking()->with('type')->first();
        $propertyId = $booking?->property_id ? (int) $booking->property_id : null;
        $isNew = $conversation === null;
        $conversation ??= $this->create(Conversation::KIND_COMPLIANCE_JOB, $offer->id, (int) $offer->account_id, $propertyId);
        $this->syncParticipants($conversation, [
            [Party::account((int) $offer->account_id), ConversationParticipant::LANDLORD],
            [Party::user((int) $offer->contractor_user_id), ConversationParticipant::CONTRACTOR],
        ], $this->tenantsAtProperty((int) $offer->account_id, $propertyId, null));

        if ($isNew) {
            $check = $booking?->type?->name ?: 'compliance check';
            $this->opened($conversation, 'The '.$check.' is booked with '.$this->contractorName($offer->contractor_user_id).'. Use this chat to arrange the visit. It is archived when the job is signed off.');
        }

        return $conversation;
    }

    /** Live job chats at a property pick up tenants moving in or out. */
    public function syncJobsAtProperty(?int $propertyId): void
    {
        if (! $propertyId) {
            return;
        }
        Conversation::query()
            ->where('property_id', $propertyId)
            ->where('status', Conversation::ACTIVE)
            ->whereIn('kind', [Conversation::KIND_REPAIR_JOB, Conversation::KIND_COMPLIANCE_JOB])
            ->get()
            ->each(fn (Conversation $conversation) => $this->syncContext($conversation));
    }

    /** Re-applies the rules to one chat (used on open and before every send). */
    public function syncContext(Conversation $conversation): Conversation
    {
        match ($conversation->kind) {
            Conversation::KIND_TENANCY => ($t = Tenancy::query()->find($conversation->context_id))
                ? $this->syncTenancy($t) : $this->archive($conversation, 'The tenancy was removed, so this chat is archived.'),
            Conversation::KIND_REPAIR_JOB => ($o = RepairJobOffer::query()->find($conversation->context_id))
                ? $this->syncRepairJob($o) : $this->archive($conversation, 'The job was removed, so this chat is archived.'),
            Conversation::KIND_COMPLIANCE_JOB => ($c = ComplianceJobOffer::query()->find($conversation->context_id))
                ? $this->syncComplianceJob($c) : $this->archive($conversation, 'The job was removed, so this chat is archived.'),
            default => null,
        };

        return $conversation->refresh()->load('participants');
    }

    /**
     * Creates any chat this party should have but does not yet (existing
     * tenancies and jobs from before messaging, or rows changed without
     * model events). Cheap: only looks at records without a chat.
     */
    public function syncForParty(Party $party): void
    {
        if ($party->isAccount()) {
            Tenancy::query()->where('account_id', $party->id)->where('status', self::TENANCY_LIVE)
                ->whereNotExists($this->chatFor(Conversation::KIND_TENANCY, 'tenancies'))
                ->limit(500)->get()->each(fn (Tenancy $t) => $this->syncTenancy($t));
            $this->liveRepairOffers()->where('account_id', $party->id)->limit(500)->get()->each(fn ($o) => $this->syncRepairJob($o));
            $this->liveComplianceOffers()->where('account_id', $party->id)->limit(500)->get()->each(fn ($o) => $this->syncComplianceJob($o));

            return;
        }

        $tenancyIds = TenantMember::query()->where('user_id', $party->id)->pluck('tenancy_id')->filter()->unique();
        if ($tenancyIds->isNotEmpty()) {
            $tenancies = Tenancy::query()->whereIn('id', $tenancyIds)->where('status', self::TENANCY_LIVE)->get();
            $tenancies->each(fn (Tenancy $t) => $this->syncTenancy($t));
            // Jobs at their home awarded before they joined, or before messaging existed.
            $this->liveRepairOffers(false)
                ->whereHas('repairIssue', fn ($q) => $q->whereIn('property_id', $tenancies->pluck('property_id')->filter()))
                ->limit(100)->get()->each(fn ($o) => $this->syncRepairJob($o));
            $this->liveComplianceOffers(false)
                ->whereHas('booking', fn ($q) => $q->whereIn('property_id', $tenancies->pluck('property_id')->filter()))
                ->limit(100)->get()->each(fn ($o) => $this->syncComplianceJob($o));
        }

        $this->liveRepairOffers()->where('contractor_user_id', $party->id)->limit(200)->get()->each(fn ($o) => $this->syncRepairJob($o));
        $this->liveComplianceOffers()->where('contractor_user_id', $party->id)->limit(200)->get()->each(fn ($o) => $this->syncComplianceJob($o));
    }

    /** Daily safety net: create missing chats and archive finished ones. */
    public function syncAll(): array
    {
        $counts = ['created' => 0, 'checked' => 0];
        $before = Conversation::query()->count();

        Tenancy::query()->where('status', self::TENANCY_LIVE)->whereNotExists($this->chatFor(Conversation::KIND_TENANCY, 'tenancies'))
            ->chunkById(200, fn ($chunk) => $chunk->each(fn (Tenancy $t) => $this->syncTenancy($t)));
        $this->liveRepairOffers()->chunkById(200, fn ($chunk) => $chunk->each(fn ($o) => $this->syncRepairJob($o)));
        $this->liveComplianceOffers()->chunkById(200, fn ($chunk) => $chunk->each(fn ($o) => $this->syncComplianceJob($o)));
        $counts['created'] = Conversation::query()->count() - $before;

        Conversation::query()->where('status', Conversation::ACTIVE)->with('participants')
            ->chunkById(200, function ($chunk) use (&$counts) {
                foreach ($chunk as $conversation) {
                    $this->syncContext($conversation);
                    $counts['checked']++;
                }
            });

        return $counts;
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private function find(string $kind, int $contextId): ?Conversation
    {
        return Conversation::query()->where('kind', $kind)->where('context_id', $contextId)->with('participants')->first();
    }

    private function create(string $kind, int $contextId, int $accountId, ?int $propertyId): Conversation
    {
        try {
            return Conversation::create([
                'kind' => $kind,
                'context_id' => $contextId,
                'account_id' => $accountId,
                'property_id' => $propertyId,
                'status' => Conversation::ACTIVE,
            ])->load('participants');
        } catch (QueryException $exception) {
            // Two requests raced to create it: the unique key kept one.
            $existing = $this->find($kind, $contextId);
            if ($existing) {
                return $existing;
            }
            throw $exception;
        }
    }

    /**
     * @param  array<int, array{0: Party, 1: string}>  $fixed  landlord / contractor
     * @param  array<int, int>  $tenantUserIds
     */
    private function syncParticipants(Conversation $conversation, array $fixed, array $tenantUserIds): void
    {
        $conversation->loadMissing('participants');
        $wanted = $fixed;
        foreach ($tenantUserIds as $userId) {
            $wanted[] = [Party::user($userId), ConversationParticipant::TENANT];
        }

        $keep = [];
        foreach ($wanted as [$party, $role]) {
            $keep[$party->key()] = true;
            $existing = $conversation->participantFor($party);
            if ($existing) {
                if ($existing->left_at !== null) {
                    $existing->forceFill(['left_at' => null, 'role' => $role])->save();
                }

                continue;
            }
            $conversation->participants()->firstOrCreate(
                ['party_type' => $party->type, 'party_id' => $party->id],
                ['role' => $role],
            );
        }

        // Only tenants come and go; the landlord and contractor are fixed by the record.
        $conversation->participants()
            ->where('role', ConversationParticipant::TENANT)
            ->whereNull('left_at')
            ->get()
            ->reject(fn (ConversationParticipant $p) => isset($keep[$p->party()->key()]))
            ->each(fn (ConversationParticipant $p) => $p->forceFill(['left_at' => now()])->save());

        $conversation->unsetRelation('participants');
        $conversation->load('participants');
    }

    private function archive(Conversation $conversation, string $reason): void
    {
        if (! $conversation->isActive()) {
            return;
        }
        $conversation->forceFill([
            'status' => Conversation::ARCHIVED,
            'archived_at' => now(),
            'archived_reason' => $reason,
        ])->save();
        $this->system($conversation, $reason);
    }

    /** A tenancy that becomes active again (e.g. status corrected) reopens its chat. */
    private function reopen(Conversation $conversation): void
    {
        if ($conversation->isActive()) {
            return;
        }
        $conversation->forceFill(['status' => Conversation::ACTIVE, 'archived_at' => null, 'archived_reason' => null])->save();
        $this->system($conversation, 'The tenancy is active again, so this chat is open.');
    }

    private function opened(Conversation $conversation, string $text): void
    {
        $message = $this->system($conversation, $text);
        $this->notifier->chatOpened($conversation->load('participants'), $message);
    }

    private function system(Conversation $conversation, string $text): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'body' => $text,
            'kind' => Message::SYSTEM,
        ]);
        $conversation->forceFill(['last_message_at' => $message->created_at, 'last_message_id' => $message->id])->save();

        return $message;
    }

    private function jobEndedReason(bool $finished): string
    {
        return $finished
            ? 'The job has been signed off, so this chat is archived.'
            : 'The job was cancelled, so this chat is archived.';
    }

    /** @return array<int, int> */
    private function tenantUserIds(array $tenancyIds): array
    {
        if ($tenancyIds === []) {
            return [];
        }

        return TenantMember::query()
            ->whereIn('tenancy_id', $tenancyIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();
    }

    /**
     * Tenants who live where the job is. If the tenant who reported a repair is
     * on one of several tenancies (e.g. rooms in a shared house), only that
     * tenancy's tenants join; otherwise everyone on an active tenancy there.
     *
     * @return array<int, int>
     */
    private function tenantsAtProperty(int $accountId, ?int $propertyId, ?int $reporterUserId): array
    {
        if (! $propertyId) {
            return [];
        }
        $tenancyIds = Tenancy::query()
            ->where('account_id', $accountId)
            ->where('property_id', $propertyId)
            ->where('status', self::TENANCY_LIVE)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($reporterUserId && count($tenancyIds) > 1) {
            $reporterTenancy = TenantMember::query()->whereIn('tenancy_id', $tenancyIds)->where('user_id', $reporterUserId)->value('tenancy_id');
            if ($reporterTenancy) {
                $tenancyIds = [(int) $reporterTenancy];
            }
        }

        return $this->tenantUserIds($tenancyIds);
    }

    private function contractorName(?int $userId): string
    {
        return $userId ? Party::user($userId)->displayName() : 'a contractor';
    }

    private function liveRepairOffers(bool $missingOnly = true)
    {
        return RepairJobOffer::query()
            ->where('status', RepairJobOffer::JOB_ACCEPTED)
            ->where(fn ($q) => $q->whereNull('job_status')->orWhereNotIn('job_status', self::JOB_DONE))
            ->when($missingOnly, fn ($q) => $q->whereNotExists($this->chatFor(Conversation::KIND_REPAIR_JOB, 'repair_job_offers')));
    }

    private function liveComplianceOffers(bool $missingOnly = true)
    {
        return ComplianceJobOffer::query()
            ->where('status', ComplianceJobOffer::JOB_ACCEPTED)
            ->where(fn ($q) => $q->whereNull('job_status')->orWhereNotIn('job_status', self::JOB_DONE))
            ->when($missingOnly, fn ($q) => $q->whereNotExists($this->chatFor(Conversation::KIND_COMPLIANCE_JOB, 'compliance_job_offers')));
    }

    private function chatFor(string $kind, string $table): \Closure
    {
        return fn ($q) => $q->select(DB::raw(1))->from('conversations')
            ->where('conversations.kind', $kind)
            ->whereColumn('conversations.context_id', $table.'.id');
    }
}
