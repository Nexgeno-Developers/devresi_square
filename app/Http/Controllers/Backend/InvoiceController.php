<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Models\Invoice;
use App\Models\TaxRates;
use App\Models\WorkOrder;
use App\Models\InvoiceItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use niklasravnsborg\LaravelPdf\Facades\Pdf;


class InvoiceController
{
    /**
    * Display a list of all invoices.
    */
    public function index(Request $request)
    {
        $query = $this->invoices()->with(['workOrder.repairIssue.property', 'user']);

        // Apply filters if provided
        if ($request->has('status')) {
            // Same ids as getInvoiceStatusDetails() and Transaction::updateInvoiceStatus() (3 is part-paid).
            $statusMap = [
                'pending' => 1,
                'paid' => 2,
                'partially_paid' => 3,
                'overdue' => 4,
                'cancelled' => 5,
            ];
            if (array_key_exists($request->status, $statusMap)) {
                $query->where('status_id', $statusMap[$request->status]);
            }
        }
    
        // if ($request->has('search')) {
        //     $query->where(function ($q) use ($request) {
        //         $q->where('invoice_number', 'LIKE', "%{$request->search}%")
        //           ->orWhereHas('user', function ($q) use ($request) {
        //               $q->where('full_name', 'LIKE', "%{$request->search}%");
        //           })
        //           ->orWhereHas('workOrder.repairIssue.property', function ($q) use ($request) {
        //               $q->where('name', 'LIKE', "%{$request->search}%");
        //           });
        //     });
        // }
    
        $invoices = $query->latest()->paginate(10); // Pagination with 10 records per page
        return view('backend.invoices.index', compact('invoices'));
    }

    /**
     * Generate an invoice from a Work Order.
     */
    public function createFromWorkOrder(Request $request, $workOrderId)
    {
        $workOrder = WorkOrder::with(['jobType', 'repairIssue.property', 'items'])
            ->when(! bypasses_account_scope(), fn ($query) => $query->forAccount(current_account_id()))
            ->findOrFail($workOrderId);

        // Check if an invoice already exists for this Work Order
        if ($workOrder->invoice) {
            return response()->json(['message' => 'Invoice already exists!'], 400);
        }

        // 🔹 Get Property ID from Work Order
        $propertyId = $workOrder->repairIssue->property->id ?? null;

        // If property ID is missing, return error
        if (!$propertyId) {
            return response()->json(['message' => 'Property ID not found!'], 400);
        }

        // Generate unique invoice number
        // $invoiceNumber = 'INV-' . str_pad(Invoice::count() + 1, 6, '0', STR_PAD_LEFT);

        // Create the invoice
        $invoiceNumber = generateReferenceNumber(Invoice::class, 'invoice_number', 'RESISQREINV');
        
        // Calculate invoice totals dynamically from Work Order items
        $subtotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;

        foreach ($workOrder->items as $item) {
            $rowSubtotal = $item->unit_price * $item->quantity;
            $taxAmount = ($rowSubtotal * $item->tax_rate) / 100;
            $rowTotal = $rowSubtotal + $taxAmount;

            $subtotal += $rowSubtotal;
            $taxTotal += $taxAmount;
            $grandTotal += $rowTotal;
        }
        
        // Include charge to landlord if applicable
        if ($workOrder->charge_to_landlord > 0) {
            $subtotal += $workOrder->charge_to_landlord;
            $grandTotal += $workOrder->charge_to_landlord;
        }
        
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'work_order_id' => $workOrder->id,
            'property_id' => $propertyId,
            'user_id' => $workOrder->invoice_to_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30), // Default 30 days due
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'notes' => $workOrder->extra_notes,
            'total_amount' => $grandTotal,
            'status_id' => 1, // "Pending" by default
            'invoiced_date_time' => now(),
            'created_by' => auth()->id(),
        ]);

        // echo "<pre>";
        // var_dump($invoice);
        // echo "</pre>";
        // exit();

        if($workOrder->items){
            // Add Work Order items as invoice items
            foreach ($workOrder->items as $item) {
                InvoiceItems::create([
                    'invoice_id' => $invoice->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total_price' => $item->unit_price * $item->quantity,
                ]);
            }
        }
        flash('Invoice generated successfully!')->success();
        return back();
        // return redirect()->route('admin.invoices.index');
        // return response()->json([
        //     'message' => 'Invoice generated successfully!',
        //     'invoice_id' => $invoice->id
        // ]);
    }

    /**
     * Show invoice details.
     */
    public function show($invoiceId)
    {
        $invoice = $this->invoices()->with(['workOrder.repairIssue.property', 'user', 'items'])->findOrFail($invoiceId);
            
        // nice helpers available on model:
        $paid = $invoice->paidAmount();
        $outstanding = $invoice->outstandingAmount();
        return view('backend.invoices.show', compact('invoice', 'paid', 'outstanding'));
    }

    /**
     * Download Invoice as PDF.
     */
    public function download($invoiceId)
    {
        $invoice = $this->invoices()->with('items', 'user')->findOrFail($invoiceId);
    
        // if (Language::where('code', $language_code)->first()->rtl == 1) {
        //     $direction = 'rtl';
        //     $text_align = 'right';
        //     $not_text_align = 'left';
        // } else {
            $direction = 'ltr';
            $text_align = 'left';
            $not_text_align = 'right';
        // }

        $pdf = Pdf::loadView('backend.invoices.invoice_pdf',[

                'direction' => $direction,
                'text_align' => $text_align,
                'not_text_align' => $not_text_align
        ], compact('invoice'));
    
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
    // public function download($invoiceId)
    // {
    //     $invoice = Invoice::with('workOrder')->findOrFail($invoiceId);
    //     $pdf = Pdf::loadView('invoices.invoice_template', compact('invoice'));

    //     return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    // }

    /**
     * Mark Invoice as Paid.
     */
    public function markAsPaid($invoiceId)
    {
        $invoice = $this->invoices()->findOrFail($invoiceId);
        $invoice->update(['status_id' => 2]); // "Paid" status

        return response()->json(['message' => 'Invoice marked as paid!']);
    }

    public function edit($invoiceId)
    {
        $invoice = $this->invoices()->with('items')->findOrFail($invoiceId);
        $users = $this->billableUsers()->get();
        $taxRates = TaxRates::all(); // Fetch all tax rates from the database

        return view('backend.invoices.edit-page', compact('invoice', 'users', 'taxRates'));
    }

    public function update(Request $request, $invoiceId)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|string|max:255',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'invoice_to' => 'required|string|max:255',
            // 'invoice_to_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch the invoice
        $invoice = $this->invoices()->findOrFail($invoiceId);
        if (! $this->billableUsers()->whereKey($request->input('user_id'))->exists()) {
            return response()->json(['message' => 'Validation Failed', 'errors' => ['user_id' => ['Choose someone on this account.']]], 422);
        }
        $workOrder = $invoice->workOrder; // Get associated Work Order

        // Recalculate totals
        $subtotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;

        foreach ($request->items as $item) {
            $rowSubtotal = $item['unit_price'] * $item['quantity'];
            $taxAmount = ($rowSubtotal * $item['tax_rate']) / 100;
            $rowTotal = $rowSubtotal + $taxAmount;

            $subtotal += $rowSubtotal;
            $taxTotal += $taxAmount;
            $grandTotal += $rowTotal;
        }

        // Include charge to landlord if applicable
        if ($workOrder && $workOrder->charge_to_landlord > 0) {
            $subtotal += $workOrder->charge_to_landlord;
            $grandTotal += $workOrder->charge_to_landlord;
        }

        // Update invoice details
        $invoice->update([
            'invoice_number' => $request->invoice_number,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'user_id' => $request->user_id,
            'notes' => $request->notes,
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'total_amount' => $grandTotal,
            'updated_by' => auth()->id(),
        ]);

        // Delete old items and add new ones
        $invoice->items()->delete();
        foreach ($request->items as $item) {
            InvoiceItems::create([
                'invoice_id' => $invoice->id,
                'title' => $item['title'],
                'description' => $item['description'] ?? '',
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'tax_rate' => $item['tax_rate'],
                'total_price' => ($item['unit_price'] * $item['quantity']),
            ]);
        }

        flash('Invoice updated successfully!')->success();
        return redirect()->route('admin.invoices.index');
    }

    /**
     * AJAX search for invoices (Select2).
     */
    public function ajaxSearch(Request $request)
    {
        $q = $request->query('q', null);
        $onlyOutstanding = (bool) $request->query('only_outstanding', false);
        $limit = (int) $request->query('limit', 50);

        $results = Invoice::ajaxSearchForSelect($q, $onlyOutstanding, $limit, bypasses_account_scope() ? null : (int) current_account_id());

        return response()->json(['results' => $results]);
    }

    /**
     * Return single invoice details (for Select2 preselect or detail fetch).
     */
    public function ajaxGet(Invoice $invoice)
    {
        abort_unless($this->invoices()->whereKey($invoice->id)->exists(), 404);

        return response()->json($invoice->toAjaxData());
    }
    

    /**
     * Repair invoices have no account column of their own; they belong to the account of their work order.
     * Everyone except a Super Admin outside any account only reaches their own account's invoices.
     */
    private function invoices()
    {
        return Invoice::query()->when(! bypasses_account_scope(), fn ($query) => $query
            ->whereHas('workOrder', fn ($workOrder) => $workOrder->forAccount(current_account_id())));
    }

    /** People an invoice can be addressed to: members of the current account. */
    private function billableUsers()
    {
        return User::query()->when(! bypasses_account_scope(), fn ($query) => $query
            ->whereIn('users.id', \App\Models\AccountUser::query()->where('account_id', current_account_id())->where('status', 'active')->select('user_id')));
    }
}
