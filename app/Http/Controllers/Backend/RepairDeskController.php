<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\User;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Repairs\RepairDeskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RepairDeskController extends Controller
{
    public function __construct(private readonly RepairDeskService $desk)
    {
    }

    public function store(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->desk->send($repairIssue, $request->user(), $data['note'] ?? null);
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route('admin.property_repairs.show', $repairIssue).'#contractors')
            ->with('success', 'Sent to the Resisquare team. They will handle this repair.');
    }

    public function withdraw(Request $request, RepairIssue $repairIssue)
    {
        $this->authorizeRepair($repairIssue);

        try {
            $this->desk->withdraw($repairIssue, $request->user());
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route('admin.property_repairs.show', $repairIssue).'#contractors')
            ->with('success', 'Taken back. You can send it to contractors.');
    }

    public function index()
    {
        $requests = RepairDeskRequest::query()
            ->with(['repairIssue.property', 'requester', 'assignee'])
            ->latest('id')
            ->paginate(25);

        $team = User::role('Super Admin')->orderBy('name')->get(['id', 'name', 'email']);

        return view('backend.saas.repair_desk.index', compact('requests', 'team'));
    }

    public function update(Request $request, RepairDeskRequest $repairDeskRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(RepairDeskRequest::STATUSES))],
            'assigned_to' => ['nullable', 'integer'],
            'team_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['assigned_to'])) {
            $member = User::query()->whereKey($data['assigned_to'])->first();
            $role = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->first();
            abort_unless($member && $role && $member->hasRole($role), 422, 'Assign a Super Admin team member.');
        }

        $this->desk->applyTeamUpdate($repairDeskRequest, [
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?: null,
            'team_note' => $data['team_note'] ?? null,
        ]);

        return redirect()
            ->route('backend.saas.repair_desk.index')
            ->with('success', 'Repair desk request updated.');
    }

    private function authorizeRepair(RepairIssue $repair): void
    {
        abort_if(is_tenant_portal_user(), 403);
        ensureModelBelongsToCurrentAccount($repair);
        Gate::authorize('update', $repair);
    }
}
