<?php

namespace App\Http\Controllers\Backend;

use Carbon\Carbon;
use App\Mail\MailManager;
use App\Models\RepairIssueContractorAssignment;
use App\Models\Upload;
use App\Models\TaxRates;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class WorkOrderController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'repair_issue_id' => 'required|exists:repair_issues,id',
            'work_order_id' => 'nullable|exists:work_orders,id',
            'job_status' => 'required|string',
            'job_type_id' => 'required|exists:job_types,id',
            'job_sub_type_id' => 'nullable|exists:job_types,id',
            'tentative_start_date' => 'nullable|date',
            'tentative_end_date' => 'nullable|date',
            'booked_date' => 'nullable|date',
            'status' => 'required|string',
            'extra_notes' => 'nullable|string',
            'final_contractor_assignment_id' => [
                'required',
                Rule::exists('repair_issue_contractor_assignments', 'id')
                    ->where(fn ($query) => $query->where('repair_issue_id', $request->repair_issue_id)),
            ],
            'items' => 'nullable|array',
            'items.*.title' => 'required|string',
            'items.*.description' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.tax_name' => 'required|string',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
        ], [
            'final_contractor_assignment_id.required' => 'Please select final contractor before saving work order.',
            'final_contractor_assignment_id.exists' => 'Selected final contractor is not valid for this repair issue.',
        ]);

        // Check if we're updating an existing Work Order
        if (!empty($request->work_order_id)) {
            $workOrder = WorkOrder::find($request->work_order_id);
        
            if (!$workOrder) {
                return response()->json([
                    'message' => 'Work Order not found!',
                    'error' => true
                ], 404);
            }
        
            $workOrder->update([
                'repair_issue_id' => $request->repair_issue_id,
                'job_type_id' => $request->job_type_id,
                'job_sub_type_id' => $request->job_sub_type_id,
                'job_status' => $request->job_status,
                'tentative_start_date' => $request->tentative_start_date,
                'tentative_end_date' => $request->tentative_end_date,
                'invoice_to' => $request->invoice_to,
                'booked_date' => $request->booked_date,
                'status' => $request->status,
                'extra_notes' => $request->extra_notes,
            ]);
        
            $workOrder->items()->delete();
        } else {
            // Creating a new Work Order
            $workOrderNumber = generateReferenceNumber(WorkOrder::class, 'works_order_no', 'RESISQREWO');
            $workOrder = WorkOrder::create([
                'works_order_no' => $workOrderNumber,
                'repair_issue_id' => $request->repair_issue_id,
                'job_type_id' => $request->job_type_id,
                'job_sub_type_id' => $request->job_sub_type_id,
                'job_status' => $request->job_status,
                'tentative_start_date' => $request->tentative_start_date,
                'tentative_end_date' => $request->tentative_end_date,
                'invoice_to' => $request->invoice_to,
                'booked_date' => $request->booked_date,
                'status' => $request->status,
                'extra_notes' => $request->extra_notes,
            ]);
        }
        
        \Log::info('Items Data:', $request->items);
        // Handle Work Order Items
        if (!empty($request->items)) {
            foreach ($request->items as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                $taxAmount = ($subTotal * $item['tax_rate']) / 100;
                $total = $subTotal + $taxAmount;

                WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? '',
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'tax_rate_id' => $item['tax_name'],
                    'tax_rate' => $item['tax_rate'],
                    'total_price' => $total,
                ]);
            }
        }

        if ($request->filled('final_contractor_assignment_id')) {
            $this->setFinalContractorFromAssignment(
                (int) $request->repair_issue_id,
                (int) $request->final_contractor_assignment_id
            );
        }

        return response()->json([
            'message' => $request->has('work_order_id') ? 'Work Order Updated Successfully!' : 'Work Order Created Successfully!',
            'workOrder' => $workOrder
        ]);
    }

    /*
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'repair_issue_id' => 'required|exists:repair_issues,id',
            'job_type_id' => 'required|exists:job_types,id',
            'job_sub_type_id' => 'nullable|exists:job_types,id',
            'job_scope' => 'nullable|string',
            'tentative_start_date' => 'nullable|date',
            'tentative_end_date' => 'nullable|date',
            'booked_date' => 'nullable|date',
            'invoice_to' => 'required|string',
            'actual_cost' => 'nullable|numeric',
            'charge_to_landlord' => 'nullable|numeric',
            'payment_by' => 'required|string',
            'estimated_cost' => 'nullable|numeric',
            'status' => 'required|string',
            'extra_notes' => 'nullable|string',
            // 'quote_attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        // Handle file upload
        // if ($request->hasFile('quote_attachment')) {
        //     $quote_attachment_id = Upload::storeFile($request->file('quote_attachment'))->id;
        // }

        if ($request->has('work_order_id')) {
            // Update existing Work Order
            $workOrderId = $request->work_order_id;
            $workOrder = WorkOrder::findOrFail($workOrderId);
            $invoiceTo = $request->invoice_to;
            $workOrder->update([
                'repair_issue_id' => $request->repair_issue_id,
                'job_type_id' => $request->job_type_id,
                'job_sub_type_id' => $request->job_sub_type_id,
                'job_status' => $request->status,
                'job_scope' => $request->job_scope,
                'tentative_start_date' => $request->tentative_start_date,
                'tentative_end_date' => $request->tentative_end_date,
                'booked_date' => $request->booked_date,
                'invoice_to' => $invoiceTo,
                'invoice_to_id' => ($request->invoice_to !== 'Company') ? ($request->invoice_to_id ?? $workOrder->invoice_to_id) : null,
                'quote_attachment' => $quote_attachment_id ?? $workOrder->quote_attachment, // Keep existing if not updated
                'actual_cost' => $request->actual_cost,
                'charge_to_landlord' => $request->charge_to_landlord,
                'payment_by' => $request->payment_by,
                'status' => $request->status,
                'extra_notes' => $request->extra_notes,
                'date_time' => $request->date_time ? Carbon::parse($request->date_time)->format('Y-m-d H:i:s') : $workOrder->date_time,
            ]);
              
            // Delete existing Work Order Items
            $workOrder->items()->delete();
            // WorkOrderItem::where('work_order_id', $workOrder->id)->delete();

            foreach ($request->items as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                $taxAmount = ($subTotal * $item['tax_rate']) / 100;
                $total = $subTotal + $taxAmount;
        
                WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'tax_rate' => $item['tax_rate'],
                    'total_price' => $total,
                ]);
            }
            
            return response()->json([
                'message' => 'Work Order Updated Successfully!',
                'workOrder' => $workOrder
            ]);
        }
        else{
            // Create Work Order
            $workOrderNumber = generateReferenceNumber(WorkOrder::class, 'works_order_no', 'RESISQREWO');
            $workOrder = WorkOrder::create([
                'works_order_no' => $workOrderNumber, // Generate a unique reference number
                'repair_issue_id' => $request->repair_issue_id,
                'job_type_id' => $request->job_type_id,
                'job_sub_type_id' => $request->job_sub_type_id,
                'job_status' => $request->job_status,
                'job_scope' => $request->job_scope,
                'tentative_start_date' => $request->tentative_start_date,
                'tentative_end_date' => $request->tentative_end_date,
                'booked_date' => $request->booked_date,
                'invoice_to' => $request->invoice_to,
                'invoice_to_id' => $request->invoice_to_id,
                // 'quote_attachment' => $quote_attachment_id ?? '',
                'actual_cost' => $request->actual_cost,
                'charge_to_landlord' => $request->charge_to_landlord,
                'payment_by' => $request->payment_by,
                // 'estimated_cost' => $request->estimated_cost,
                'status' => $request->status,
                'extra_notes' => $request->extra_notes,
                'date_time' => Carbon::parse($request->date_time)->format('Y-m-d H:i:s'),
            ]);

            foreach ($request->items as $item) {
                $subTotal = $item['unit_price'] * $item['quantity'];
                $taxAmount = ($subTotal * $item['tax_rate']) / 100;
                $total = $subTotal + $taxAmount;
        
                WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'tax_rate' => $item['tax_rate'],
                    'total_price' => $total,
                ]);
            }

            return response()->json(['message' => 'Work Order Created Successfully!', 'workOrder' => $workOrder]);
            // flash('Work Order Created Successfully!')->success();
            // return back()->json(['workOrder' => $workOrder]);
            // return back();
        }
    }
    */

    public function getWorkOrder($repairIssueId)
    {
        $workOrder = WorkOrder::where('repair_issue_id', $repairIssueId)->first();

        return response()->json([
            'work_order' => $workOrder
        ]);
    }

    public function generateWorkOrderPDF($id)
    {
        $workorder = $this->workOrderForPdf((int) $id);
        $pdf = $this->buildWorkOrderPdf($workorder);

        // Download PDF
        return $pdf->download('workorder-invoice-' . $workorder->works_order_no . '.pdf');
    }

    public function sendWorkOrder($id)
    {
        $workorder = $this->workOrderForPdf((int) $id);
        $contractor = $workorder->repairIssue?->finalContractor;

        if (! $contractor?->email) {
            return response()->json(['message' => 'Select a final contractor with an email before sending the work order.'], 422);
        }

        $pdf = $this->buildWorkOrderPdf($workorder);

        Mail::to($contractor->email)->send(new MailManager([
            'subject' => 'Work order - ' . $workorder->works_order_no,
            'content' => view('emails.repair_work_assigned', [
                'repairIssue' => $workorder->repairIssue,
                'contractor' => $contractor,
                'assignment' => RepairIssueContractorAssignment::where('repair_issue_id', $workorder->repair_issue_id)
                    ->where('contractor_id', $contractor->id)
                    ->first(),
            ])->render(),
            'attachments' => [[
                'type' => 'data',
                'data' => $pdf->output(),
                'name' => 'work-order-' . $workorder->works_order_no . '.pdf',
                'options' => ['mime' => 'application/pdf'],
            ]],
        ]));

        return response()->json(['message' => 'Work order sent to final contractor successfully.']);
    }

    private function setFinalContractorFromAssignment(int $repairIssueId, int $assignmentId): void
    {
        $assignment = RepairIssueContractorAssignment::where('repair_issue_id', $repairIssueId)
            ->findOrFail($assignmentId);

        $assignment->repairIssue()->update([
            'final_contractor_id' => $assignment->contractor_id,
        ]);

        RepairIssueContractorAssignment::where('repair_issue_id', $repairIssueId)
            ->where('status', 'Finalized')
            ->update(['status' => 'Proposed']);

        $assignment->update(['status' => 'Finalized']);
    }

    private function workOrderForPdf(int $id): WorkOrder
    {
        return WorkOrder::with([
            'items',
            'jobType',
            'jobSubType',
            'repairIssue.finalContractor',
            'repairIssue.property.creator',
            'repairIssue.repairCategory',
            'repairIssue.tenant',
        ])->findOrFail($id);
    }

    private function buildWorkOrderPdf(WorkOrder $workorder)
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        return Pdf::loadView('backend.work_orders.work_order_pdf', [
            'workorder' => $workorder,
            'taxRates' => TaxRates::all(),
            'direction' => 'ltr',
            'text_align' => 'left',
            'not_text_align' => 'right',
        ], [], [
            'tempDir' => $tempDir,
        ]);
    }

}
