<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class UnchargedRepairWorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with([
            'items',
            'invoice',
            'repairIssue.property',
            'repairIssue.finalContractor',
        ])
            ->whereHas('repairIssue', fn ($q) => $q->whereNotNull('final_contractor_id'))
            ->whereDoesntHave('invoice');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('works_order_no', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%")
                    ->orWhereHas('repairIssue', function ($repairQuery) use ($search) {
                        $repairQuery->where('reference_number', 'LIKE', "%{$search}%")
                            ->orWhereHas('property', function ($propertyQuery) use ($search) {
                                $propertyQuery->where('prop_name', 'LIKE', "%{$search}%")
                                    ->orWhere('prop_ref_no', 'LIKE', "%{$search}%")
                                    ->orWhere('line_1', 'LIKE', "%{$search}%")
                                    ->orWhere('city', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('finalContractor', function ($contractorQuery) use ($search) {
                                $contractorQuery->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('email', 'LIKE', "%{$search}%");
                            });
                    });
            });
        }

        $workOrders = $query->orderByDesc('id')->paginate(15);

        return view('backend.accounting.uncharged_repair_work_orders.index', compact('workOrders'));
    }
}
