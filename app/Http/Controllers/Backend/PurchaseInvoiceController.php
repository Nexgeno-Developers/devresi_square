<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $invoices = PurchaseInvoice::orderBy('invoice_date','desc')->paginate(25);
        return view('backend.purchase_invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('backend.purchase_invoices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'property_id' => 'nullable|exists:properties,id',
            'reference' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'nullable|array'
        ]);

        DB::transaction(function () use ($data) {
            $invoiceNumber = function_exists('generateDocumentNumber') 
                ? generateDocumentNumber('invoice_purchase','PINV')
                : DocumentSequence::generate('invoice_purchase','PINV');

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $data['supplier_id'],
                'property_id' => $data['property_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['tax_amount'] ?? 0,
                'total_amount' => $data['total_amount'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $it) {
                    $invoice->items()->create([
                        'title' => $it['title'] ?? null,
                        'description' => $it['description'] ?? null,
                        'unit_price' => $it['unit_price'] ?? 0,
                        'quantity' => $it['quantity'] ?? 1,
                        'total_price' => $it['total_price'] ?? 0,
                        'tax_rate' => $it['tax_rate'] ?? 0,
                        'tax_rate_id' => $it['tax_rate_id'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('backend.purchase_invoices.index')->with('success','Purchase invoice created');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        return view('backend.purchase_invoices.show', ['invoice' => $purchaseInvoice]);
    }

    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        return view('backend.purchase_invoices.edit', ['invoice' => $purchaseInvoice]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'property_id' => 'nullable|exists:properties,id',
            'reference' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $purchaseInvoice->update($data);
        return redirect()->route('backend.purchase_invoices.show', $purchaseInvoice->id)->with('success','Purchase invoice updated');
    }
}
