<?php

namespace App\Services\Repairs;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\RepairContractorRequest;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Notifications\CrmNotificationService;
use App\Support\AccountMembership;
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
        if (in_array($repair->status, ['Closed', 'Invoice Paid', 'Cancelled', 'Rejected'], true)) {
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

        return RepairDeskRequest::query()->create([
            'account_id' => $repair->account_id ?: current_account_id(),
            'repair_issue_id' => $repair->id,
            'requested_by' => $landlord->id,
            'status' => RepairDeskRequest::REQUESTED,
            'landlord_note' => filled($note) ? trim($note) : null,
        ]);
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

        return $desk;
    }

    /**
     * @param  array{status: string, assigned_to: int|null, team_note: string|null}  $data
     */
    public function applyTeamUpdate(RepairDeskRequest $desk, array $data): RepairDeskRequest
    {
        $previous = (string) $desk->status;
        $desk->fill([
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?: null,
            'team_note' => filled($data['team_note'] ?? null) ? trim((string) $data['team_note']) : $desk->team_note,
        ])->save();

        if ($previous === $desk->status) {
            return $desk;
        }

        if ($desk->status === RepairDeskRequest::IN_PROGRESS) {
            $this->moveRepair($desk, ['Pending', 'Reported'], 'Under Process');
        }

        if ($desk->status === RepairDeskRequest::COMPLETED) {
            $this->moveRepair($desk, ['Pending', 'Reported', 'Under Process'], 'Work Completed');
            $this->notifyCompleted($desk);
        }

        return $desk;
    }

    public function assertMarketplaceAvailable(RepairIssue $repair): void
    {
        if ($this->openFor($repair)) {
            throw new ContractorMarketplaceException('This repair is with the Resisquare team. Take it back before you send it to contractors.');
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

    private function notifyCompleted(RepairDeskRequest $desk): void
    {
        $repair = $desk->repairIssue;
        if (! $repair) {
            return;
        }

        $repair->loadMissing('property');
        $address = $repair->property?->full_address ?: ($repair->property?->line_1 ?: 'the property');
        $reference = $repair->reference_number ?: ('#'.$repair->id);
        $note = filled($desk->team_note) ? ' '.trim((string) $desk->team_note) : '';
        $accountId = (int) $desk->account_id;

        $ids = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_ADMIN_TYPES)
            ->pluck('user_id');
        $ids->push($desk->requested_by);
        if ($repair->tenant_id) {
            $ids->push($repair->tenant_id);
        }

        $recipients = User::query()->whereKey($ids->filter()->unique()->all())->get();
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            app(CrmNotificationService::class)->dispatch(CrmNotificationEvent::RepairJobCompleted, $repair, [
                'account_id' => $accountId,
                'recipients' => $recipients,
                'notice_subject' => 'Repair completed',
                'notice' => 'The Resisquare team marked the repair at '.$address.' complete.'.$note,
                'repair_reference' => $reference,
                'milestone' => 'desk-completed-'.$desk->id,
                'action_url' => route('admin.property_repairs.show', $repair),
                'portal_action_url' => route('tenant.maintenance'),
                'portal_action_tenants_only' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Repair desk completion notice failed', [
                'repair_desk_request_id' => $desk->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
