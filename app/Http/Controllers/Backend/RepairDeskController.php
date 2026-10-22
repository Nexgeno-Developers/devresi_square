<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RepairDeskRequest;
use App\Models\RepairIssue;
use App\Models\User;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Repairs\RepairDeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
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

    public function index(Request $request): View
    {
        $state = (string) $request->query('state', 'open');
        if (! in_array($state, ['open', 'done', 'returned'], true)) {
            $state = 'open';
        }

        $requests = RepairDeskRequest::query()
            ->with(['repairIssue.property', 'requester', 'assignee', 'account'])
            ->when($state === 'done', fn ($query) => $query->where('status', RepairDeskRequest::COMPLETED))
            ->when($state === 'returned', fn ($query) => $query->where('status', RepairDeskRequest::RETURNED))
            ->when($state === 'open', fn ($query) => $query->whereIn('status', RepairDeskRequest::OPEN))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'open' => RepairDeskRequest::query()->whereIn('status', RepairDeskRequest::OPEN)->count(),
            'done' => RepairDeskRequest::query()->where('status', RepairDeskRequest::COMPLETED)->count(),
            'returned' => RepairDeskRequest::query()->where('status', RepairDeskRequest::RETURNED)->count(),
        ];

        return view('backend.saas.repair_desk.index', compact('requests', 'state', 'counts'));
    }

    public function show(RepairDeskRequest $repairDeskRequest): View
    {
        $repairDeskRequest->load([
            'repairIssue.property',
            'repairIssue.repairCategory',
            'repairIssue.tenant',
            'requester',
            'assignee',
            'account',
        ]);
        if (Schema::hasTable('repair_desk_events')) {
            $repairDeskRequest->load('events.actor');
        }

        return view('backend.saas.repair_desk.show', ['desk' => $repairDeskRequest]);
    }

    public function take(Request $request, RepairDeskRequest $repairDeskRequest): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        return $this->act($repairDeskRequest, function () use ($repairDeskRequest, $request, $data) {
            $this->desk->take($repairDeskRequest, $request->user(), $data['note'] ?? null, (int) $request->user()->id);
        }, 'You have this repair. The landlord and tenant have been told.');
    }

    public function progress(Request $request, RepairDeskRequest $repairDeskRequest): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        return $this->act($repairDeskRequest, function () use ($repairDeskRequest, $request, $data) {
            $this->desk->progress($repairDeskRequest, $request->user(), $data['note']);
        }, 'Update sent to the landlord and tenant.');
    }

    public function complete(Request $request, RepairDeskRequest $repairDeskRequest): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        return $this->act($repairDeskRequest, function () use ($repairDeskRequest, $request, $data) {
            $this->desk->complete($repairDeskRequest, $request->user(), $data['note']);
        }, 'Marked complete. The landlord and tenant have been told.');
    }

    public function giveBack(Request $request, RepairDeskRequest $repairDeskRequest): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        return $this->act($repairDeskRequest, function () use ($repairDeskRequest, $request, $data) {
            $this->desk->giveBack($repairDeskRequest, $request->user(), $data['note']);
        }, 'Returned to the landlord.');
    }

    public function update(Request $request, RepairDeskRequest $repairDeskRequest): RedirectResponse
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

        try {
            $this->desk->applyTeamUpdate($repairDeskRequest, [
                'status' => $data['status'],
                'assigned_to' => $data['assigned_to'] ?: null,
                'team_note' => $data['team_note'] ?? null,
            ], $request->user());
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backend.saas.repair_desk.show', $repairDeskRequest)
            ->with('success', 'Repair desk request updated.');
    }

    private function act(RepairDeskRequest $desk, callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (ContractorMarketplaceException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backend.saas.repair_desk.show', $desk)
            ->with('success', $success);
    }

    private function authorizeRepair(RepairIssue $repair): void
    {
        abort_if(is_tenant_portal_user(), 403);
        ensureModelBelongsToCurrentAccount($repair);
        Gate::authorize('update', $repair);
    }
}
