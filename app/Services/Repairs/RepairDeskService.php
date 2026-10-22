<?php

namespace App\Services\Repairs;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\RepairContractorRequest;
use App\Models\RepairDeskEvent;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Notifications\CrmNotificationService;
use App\Support\AccountMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RepairDeskService
{
    public function openFor(RepairIssue $repair): ?RepairDeskRequest
    {
        if (! Schema::hasTable('repair_desk_requests')) {
            return null;
        }

        return RepairDeskRequest::query()
            ->where('repair_issue_id', $repair->id)
            ->whereIn('status', RepairDeskRequest::OPEN)
            ->latest('id')
            ->first();
    }

    public function send(RepairIssue $repair, User $landlord, ?string $note = null): RepairDeskRequest
    {
        if ($repair->awarded_offer_id) {
            throw new ContractorMarketplaceException('This repair already has an accepted contractor.');
        }
        if (in_array($repair->status, ['Closed', 'Invoice Paid', 'Cancelled', 'Rejected', 'Work Completed'], true)) {
            throw new ContractorMarketplaceException('This repair is closed.');
        }
        if ($this->openFor($repair)) {
            throw new ContractorMarketplaceException('This repair is already with the Resisquare team.');
        }
        if ($this->marketplaceIsOpen($repair)) {
            throw new ContractorMarketplaceException('Contractors have already been asked. Close that request before sending it to Resisquare.');
        }
        if (! Schema::hasTable('repair_desk_requests')) {
            throw new ContractorMarketplaceException('The Resisquare desk is not set up on this workspace yet.');
        }

        $desk = RepairDeskRequest::query()->create([
            'account_id' => $repair->account_id ?: current_account_id(),
            'repair_issue_id' => $repair->id,
            'requested_by' => $landlord->id,
            'status' => RepairDeskRequest::REQUESTED,
            'landlord_note' => filled($note) ? trim($note) : null,
        ]);

        $body = filled($note) ? trim($note) : 'Sent to the Resisquare team.';
        $this->event($desk, $landlord, RepairDeskEvent::SENT, $body);
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobProgress,
            'Repair sent to Resisquare',
            'Your landlord asked Resisquare to handle this repair.'.(filled($note) ? ' '.trim($note) : ''),
            'desk-sent-'.$desk->id,
            false,
        );

        return $desk;
    }

    public function withdraw(RepairIssue $repair, User $landlord): RepairDeskRequest
    {
        $desk = $this->openFor($repair);
        $accountId = (int) ($repair->account_id ?: current_account_id());
        if (! $desk || (int) $desk->account_id !== $accountId) {
            throw new ContractorMarketplaceException('There is no Resisquare request to take back.');
        }
        if ($desk->status !== RepairDeskRequest::REQUESTED) {
            throw new ContractorMarketplaceException('The Resisquare team has already started. Ask them to return it.');
        }

        $desk->status = RepairDeskRequest::RETURNED;
        $desk->save();
        $this->event($desk, $landlord, RepairDeskEvent::RETURNED, 'The landlord took this repair back.');
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobProgress,
            'Repair back with your landlord',
            'Your landlord took this repair back from Resisquare.',
            'desk-withdrawn-'.$desk->id,
            false,
        );

        return $desk;
    }

    public function take(RepairDeskRequest $desk, User $actor, ?string $note = null, ?int $assigneeId = null): RepairDeskRequest
    {
        if ($desk->status !== RepairDeskRequest::REQUESTED) {
            throw new ContractorMarketplaceException('This repair is no longer waiting to be taken.');
        }

        $assignee = $this->teamMember($assigneeId) ?: $actor;
        $desk->forceFill([
            'status' => RepairDeskRequest::IN_PROGRESS,
            'assigned_to' => $assignee->id,
            'team_note' => filled($note) ? trim($note) : $desk->team_note,
        ])->save();
        $this->moveRepair($desk, ['Pending', 'Reported'], 'Under Process');
        $repair = $desk->repairIssue;
        if ($repair && ! $repair->dispatched_at) {
            $repair->forceFill([
                'dispatched_at' => now(),
                'dispatched_by' => $assignee->id,
            ])->save();
        }

        $body = filled($note) ? trim($note) : 'The Resisquare team is handling this repair.';
        $this->event($desk, $assignee, RepairDeskEvent::TAKEN, $body);
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobProgress,
            'Resisquare has your repair',
            'The Resisquare team is handling this repair.'.(filled($note) ? ' '.trim($note) : ''),
            'desk-taken-'.$desk->id,
        );

        return $desk;
    }

    public function progress(RepairDeskRequest $desk, User $actor, string $note): RepairDeskRequest
    {
        if ($desk->status !== RepairDeskRequest::IN_PROGRESS) {
            throw new ContractorMarketplaceException('Take the repair before sending an update.');
        }

        $note = trim($note);
        if ($note === '') {
            throw new ContractorMarketplaceException('Write what the landlord and tenant should know.');
        }

        $desk->forceFill([
            'assigned_to' => $desk->assigned_to ?: $actor->id,
            'team_note' => $note,
        ])->save();
        $event = $this->event($desk, $actor, RepairDeskEvent::UPDATE, $note);
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobProgress,
            'Repair update',
            $note,
            'desk-update-'.($event?->id ?: $desk->id.'-'.now()->timestamp),
        );

        return $desk;
    }

    public function complete(RepairDeskRequest $desk, User $actor, ?string $note = null): RepairDeskRequest
    {
        if (! in_array($desk->status, [RepairDeskRequest::REQUESTED, RepairDeskRequest::IN_PROGRESS], true)) {
            throw new ContractorMarketplaceException('This repair is already finished.');
        }

        $note = filled($note) ? trim($note) : null;
        $desk->forceFill([
            'status' => RepairDeskRequest::COMPLETED,
            'assigned_to' => $desk->assigned_to ?: $actor->id,
            'team_note' => $note ?: $desk->team_note,
        ])->save();
        $this->moveRepair($desk, ['Pending', 'Reported', 'Under Process'], 'Work Completed');
        $repair = $desk->repairIssue;
        if ($repair && $repair->status === 'Work Completed') {
            $repair->forceFill([
                'resolved_at' => $repair->resolved_at ?: now(),
                'resolved_by' => $repair->resolved_by ?: $actor->id,
            ])->save();
        }

        $body = $note ?: 'The Resisquare team marked this repair complete.';
        $this->event($desk, $actor, RepairDeskEvent::COMPLETED, $body);
        $address = $this->address($desk);
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobCompleted,
            'Repair completed',
            'The Resisquare team marked the repair at '.$address.' complete.'.($note ? ' '.$note : ''),
            'desk-completed-'.$desk->id,
        );

        return $desk;
    }

    public function giveBack(RepairDeskRequest $desk, User $actor, ?string $note = null): RepairDeskRequest
    {
        if (! in_array($desk->status, [RepairDeskRequest::REQUESTED, RepairDeskRequest::IN_PROGRESS], true)) {
            throw new ContractorMarketplaceException('This repair has already left the desk.');
        }

        $note = filled($note) ? trim($note) : 'Returned to the landlord.';
        $desk->forceFill([
            'status' => RepairDeskRequest::RETURNED,
            'assigned_to' => $desk->assigned_to ?: $actor->id,
            'team_note' => $note,
        ])->save();
        $this->moveRepair($desk, ['Under Process'], 'Pending');
        $repair = $desk->repairIssue?->fresh();
        if ($repair && ! $repair->awarded_offer_id && $repair->dispatched_by && (int) $repair->dispatched_by === (int) $desk->assigned_to) {
            $repair->forceFill([
                'dispatched_at' => null,
                'dispatched_by' => null,
            ])->save();
        }
        $this->event($desk, $actor, RepairDeskEvent::RETURNED, $note);
        $this->notify(
            $desk,
            CrmNotificationEvent::RepairJobProgress,
            'Repair returned',
            'Resisquare returned this repair. '.$note,
            'desk-returned-'.$desk->id,
        );

        return $desk;
    }

    /**
     * @param  array{status: string, assigned_to: int|null, team_note: string|null}  $data
     */
    public function applyTeamUpdate(RepairDeskRequest $desk, array $data, ?User $actor = null): RepairDeskRequest
    {
        $actor = $actor ?: auth()->user();
        if (! $actor instanceof User) {
            throw new ContractorMarketplaceException('Sign in to update this repair.');
        }

        $note = filled($data['team_note'] ?? null) ? trim((string) $data['team_note']) : null;
        $next = (string) $data['status'];
        $previous = (string) $desk->status;

        if ($next === RepairDeskRequest::IN_PROGRESS && $previous !== RepairDeskRequest::IN_PROGRESS) {
            return $this->take($desk, $actor, $note, $data['assigned_to'] ?? null);
        }
        if ($next === RepairDeskRequest::COMPLETED && $previous !== RepairDeskRequest::COMPLETED) {
            return $this->complete($desk, $actor, $note);
        }
        if ($next === RepairDeskRequest::RETURNED && $previous !== RepairDeskRequest::RETURNED) {
            return $this->giveBack($desk, $actor, $note);
        }
        if ($note && $note !== (string) $desk->team_note && $desk->status === RepairDeskRequest::IN_PROGRESS) {
            return $this->progress($desk, $actor, $note);
        }

        $desk->fill([
            'assigned_to' => $data['assigned_to'] ?: $desk->assigned_to,
            'team_note' => $note ?: $desk->team_note,
        ])->save();

        return $desk;
    }

    public function completedFor(RepairIssue $repair): bool
    {
        if (! Schema::hasTable('repair_desk_requests')) {
            return false;
        }

        $latest = RepairDeskRequest::query()
            ->where('repair_issue_id', $repair->id)
            ->latest('id')
            ->first();

        return $latest?->status === RepairDeskRequest::COMPLETED;
    }

    public function assertMarketplaceAvailable(RepairIssue $repair): void
    {
        if ($this->openFor($repair)) {
            throw new ContractorMarketplaceException('The Resisquare team has this repair. A contractor can be invited only if they return it.');
        }
        if ($this->completedFor($repair)) {
            throw new ContractorMarketplaceException('The Resisquare team has finished this repair.');
        }
    }

    public function marketplaceIsOpen(RepairIssue $repair): bool
    {
        $request = $repair->relationLoaded('contractorRequest')
            ? $repair->contractorRequest
            : $repair->contractorRequest()->first();

        if ($request && in_array($request->status, [RepairContractorRequest::SENT, RepairContractorRequest::AWAITING], true)) {
            return true;
        }

        return $repair->jobOffers()->whereIn('status', RepairJobOffer::OPEN_STATUSES)->exists();
    }

    /**
     * @param  array<int, string>  $from
     */
    private function moveRepair(RepairDeskRequest $desk, array $from, string $status): void
    {
        $repair = $desk->repairIssue;
        if (! $repair || ! in_array((string) $repair->status, $from, true)) {
            return;
        }

        $repair->forceFill([
            'status' => $status,
            'sub_status' => $status,
        ])->save();
    }

    private function event(RepairDeskRequest $desk, ?User $actor, string $kind, string $body): ?RepairDeskEvent
    {
        if (! Schema::hasTable('repair_desk_events')) {
            return null;
        }

        return RepairDeskEvent::query()->create([
            'repair_desk_request_id' => $desk->id,
            'account_id' => (int) $desk->account_id,
            'actor_user_id' => $actor?->id,
            'kind' => $kind,
            'body' => $body,
        ]);
    }

    private function teamMember(?int $userId): ?User
    {
        if (! $userId) {
            return null;
        }

        $member = User::query()->whereKey($userId)->first();

        return $member && $member->hasRole('Super Admin') ? $member : null;
    }

    private function address(RepairDeskRequest $desk): string
    {
        $repair = $desk->repairIssue;
        $repair?->loadMissing('property');

        return $repair?->property?->full_address
            ?: ($repair?->property?->line_1 ?: 'the property');
    }

    private function notify(
        RepairDeskRequest $desk,
        CrmNotificationEvent $event,
        string $subject,
        string $notice,
        string $milestone,
        bool $includeLandlords = true,
    ): void {
        $repair = $desk->repairIssue;
        if (! $repair) {
            return;
        }

        $recipients = $this->parties($desk, $includeLandlords);
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            app(CrmNotificationService::class)->dispatch($event, $repair, [
                'account_id' => (int) $desk->account_id,
                'recipients' => $recipients,
                'notice_subject' => $subject,
                'notice' => $notice,
                'repair_reference' => $repair->reference_number ?: ('#'.$repair->id),
                'milestone' => $milestone,
                'action_url' => route('admin.property_repairs.show', $repair),
                'portal_action_url' => route('tenant.maintenance'),
                'portal_action_tenants_only' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Repair desk notice failed', [
                'repair_desk_request_id' => $desk->id,
                'milestone' => $milestone,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function parties(RepairDeskRequest $desk, bool $includeLandlords): Collection
    {
        $repair = $desk->repairIssue;
        $ids = collect();
        if ($includeLandlords) {
            $ids = AccountUser::query()
                ->where('account_id', (int) $desk->account_id)
                ->where('status', 'active')
                ->whereIn('member_type', AccountMembership::WORKSPACE_ADMIN_TYPES)
                ->pluck('user_id');
            $ids->push($desk->requested_by);
        }
        if ($repair?->tenant_id) {
            $ids->push($repair->tenant_id);
        }

        return User::query()->whereKey($ids->filter()->unique()->all())->get();
    }
}
