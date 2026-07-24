<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\RepairIssue;
use App\Services\Saas\PortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractorPortalController extends Controller
{
    /**
     * List all repairs assigned to the logged-in contractor.
     */
    public function index(Request $request)
    {
        $contractorId = Auth::id();
        $user = Auth::user();
        $accountId = current_account_id();
        $propertyIds = [];

        if ($user && $accountId) {
            $propertyIds = app(PortalAccessService::class)->accessiblePropertyIds($user, $accountId);
        }

        $query = RepairIssue::with([
            'property',
            'repairCategory',
        ])->where(function ($repairQuery) use ($contractorId, $propertyIds) {
            $repairQuery->where('final_contractor_id', $contractorId);

            if (! empty($propertyIds)) {
                $repairQuery->orWhereIn('property_id', $propertyIds);
            }
        });

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('property', function ($q2) use ($search) {
                      $q2->where('prop_name', 'LIKE', "%{$search}%")
                         ->orWhere('prop_ref_no', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Status filter
        $allowed = ['Pending','Reported','Under Process','Work Completed','Invoice Received','Invoice Paid','Closed'];
        if ($request->filled('status') && in_array($request->status, $allowed)) {
            $query->where('status', $request->status);
        }

        $repairIssues = $query->orderByDesc('id')->paginate(10);

        // AJAX list refresh
        if ($request->ajax()) {
            $selectedRepairId = $repairIssues->first()?->id ?? null;
            return view('frontend.contractor.list.cards', compact('repairIssues', 'selectedRepairId'))->render();
        }

        $firstRepairIssue = $repairIssues->first();

        return view('frontend.contractor.index', compact('repairIssues', 'firstRepairIssue'));
    }

    /**
     * AJAX detail panel — contractor-safe (no contractor assignments, no invoice, no edit buttons).
     */
    public function show(Request $request, $id)
    {
        $contractorId = Auth::id();
        $user = Auth::user();
        $accountId = current_account_id();
        $propertyIds = [];

        if ($user && $accountId) {
            $propertyIds = app(PortalAccessService::class)->accessiblePropertyIds($user, $accountId);
        }

        $repairIssue = RepairIssue::with([
            'property',
            'repairCategory',
            'repairPhotos',
            'repairHistories',
            'repairIssuePropertyManagers.propertyManager',
            'workOrder.items',
            'tenant',
        ])->where(function ($repairQuery) use ($contractorId, $propertyIds) {
            $repairQuery->where('final_contractor_id', $contractorId);

            if (! empty($propertyIds)) {
                $repairQuery->orWhereIn('property_id', $propertyIds);
            }
        })
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->findOrFail($id);

        if ($request->ajax()) {
            return view('frontend.contractor.detail.show', compact('repairIssue'));
        }

        // Direct URL access — render full page with this repair pre-selected
        $repairIssues = RepairIssue::with(['property','repairCategory'])
            ->where(function ($repairQuery) use ($contractorId, $propertyIds) {
                $repairQuery->where('final_contractor_id', $contractorId);

                if (! empty($propertyIds)) {
                    $repairQuery->orWhereIn('property_id', $propertyIds);
                }
            })
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->orderByDesc('id')->paginate(10);

        $firstRepairIssue = $repairIssue;

        return view('frontend.contractor.index', compact('repairIssues', 'firstRepairIssue'));
    }
}
