<?php

namespace App\Services\Repairs;

use App\Models\RepairContractorRequest;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Services\Contractors\ContractorMarketplaceException;
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
}
