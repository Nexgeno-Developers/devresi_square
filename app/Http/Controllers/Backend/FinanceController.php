<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Services\Finance\RentFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, RentFinanceService $finance): View
    {
        $this->authorize('viewAny', RentInvoice::class);

        $invoices = RentInvoice::query()
            ->with(['property', 'tenant', 'tenancy'])
            ->forAccount(current_account_id())
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('backend.finance.index', [
            'invoices' => $invoices,
            'canCreate' => $finance->billableTenancies((int) current_account_id())->isNotEmpty(),
            'orphanTenancies' => Tenancy::query()
                ->forAccount((int) current_account_id())
                ->where(function ($query) {
                    $query->whereNull('property_id')->orWhere('property_id', 0);
                })
                ->where('status', 'Active')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(RentFinanceService $finance): View
    {
        $this->authorize('create', RentInvoice::class);

        $tenancies = $finance->billableTenancies((int) current_account_id());

        return view('backend.finance.create', compact('tenancies'));
    }

    public function store(Request $request, RentFinanceService $finance): RedirectResponse
    {
        $this->authorize('create', RentInvoice::class);

        $validated = $request->validate([
            'tenancy_id' => ['required', 'integer'],
            'tenant_user_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $invoice = $finance->createInvoice((int) current_account_id(), $validated);

        flash('Rent invoice '.$invoice->invoice_no.' issued.')->success();

        return redirect()->route('admin.finance.show', $invoice);
    }

    public function show(RentInvoice $rentInvoice): View
    {
        $this->authorize('view', $rentInvoice);
        ensureModelBelongsToCurrentAccount($rentInvoice);

        $rentInvoice->load(['property', 'tenant', 'tenancy', 'payments.recordedBy']);

        return view('backend.finance.show', [
            'invoice' => $rentInvoice,
            'methods' => RentPayment::METHODS,
            'manualMethods' => RentPayment::MANUAL_METHODS,
        ]);
    }

    public function storePayment(Request $request, RentInvoice $rentInvoice, RentFinanceService $finance): RedirectResponse
    {
        $this->authorize('update', $rentInvoice);
        ensureModelBelongsToCurrentAccount($rentInvoice);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'in:'.implode(',', array_keys(RentPayment::MANUAL_METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $finance->recordPayment($rentInvoice, $validated, (int) $request->user()->id);

        flash('Payment recorded.')->success();

        return redirect()->route('admin.finance.show', $rentInvoice);
    }

    public function void(RentInvoice $rentInvoice, RentFinanceService $finance): RedirectResponse
    {
        $this->authorize('update', $rentInvoice);
        ensureModelBelongsToCurrentAccount($rentInvoice);

        $finance->voidInvoice($rentInvoice);

        flash('Invoice voided.')->success();

        return redirect()->route('admin.finance.show', $rentInvoice);
    }
}
