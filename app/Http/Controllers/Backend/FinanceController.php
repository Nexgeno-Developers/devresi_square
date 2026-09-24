<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Models\BankDetails;
use App\Services\Finance\RentFinanceService;
use App\Services\Finance\RentStripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, RentFinanceService $finance): View
    {
        $this->authorize('viewAny', RentInvoice::class);

        $accountId = (int) current_account_id();
        $filter = (string) $request->query('status', 'all');

        $query = RentInvoice::query()
            ->with(['property', 'tenant', 'tenancy'])
            ->forAccount($accountId);

        if ($filter === 'overdue') {
            $query->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
                ->where('balance', '>', 0)
                ->whereDate('due_date', '<', now()->toDateString());
        } elseif ($filter === 'open') {
            $query->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
                ->where('balance', '>', 0);
        } elseif ($filter === 'paid') {
            $query->where('status', RentInvoice::STATUS_PAID);
        }

        $invoices = $query
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.finance.index', [
            'invoices' => $invoices,
            'filter' => $filter,
            'overdueCount' => $finance->overdueOpenCount($accountId),
            'rentBank' => $finance->rentPayBankDetails($accountId),
            'cardRentReady' => app(RentStripeCheckoutService::class)->isConfigured(),
            'canCreate' => $finance->billableTenancies($accountId)->isNotEmpty(),
            'orphanTenancies' => Tenancy::query()
                ->forAccount($accountId)
                ->where(function ($query) {
                    $query->whereNull('property_id')->orWhere('property_id', 0);
                })
                ->where('status', 'Active')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function rentPay(RentFinanceService $finance, RentStripeCheckoutService $rentCheckout): View
    {
        $this->authorize('viewAny', RentInvoice::class);
        $accountId = (int) current_account_id();

        return view('backend.finance.rent-pay', [
            'rentBank' => $finance->rentPayBankDetails($accountId),
            'cardRentReady' => $rentCheckout->isConfigured(),
        ]);
    }

    public function storeRentPayBank(Request $request, RentFinanceService $finance): RedirectResponse
    {
        $this->authorize('create', RentInvoice::class);

        $accountId = (int) current_account_id();
        abort_unless($accountId > 0, 403);

        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'account_no' => ['required', 'string', 'max:255'],
            'sort_code' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = $finance->rentPayBankDetails($accountId);

        BankDetails::query()
            ->forAccount($accountId)
            ->where('user_id', $request->user()->id)
            ->update(['is_primary' => false]);

        if ($existing && (int) $existing->user_id === (int) $request->user()->id) {
            $existing->update([
                ...$validated,
                'is_active' => true,
                'is_primary' => true,
                'account_id' => $accountId,
            ]);
        } else {
            BankDetails::create([
                ...$validated,
                'account_id' => $accountId,
                'user_id' => $request->user()->id,
                'is_active' => true,
                'is_primary' => true,
            ]);
        }

        flash('Bank transfer details saved. Tenants see these when card rent is off.')->success();

        return redirect()->route('admin.finance.rent-pay');
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
            'auto_recurring' => ['nullable', 'boolean'],
        ]);

        $invoice = $finance->createInvoice((int) current_account_id(), $validated);

        if ($request->boolean('auto_recurring')) {
            $tenancy = Tenancy::query()->forAccount((int) current_account_id())->findOrFail($validated['tenancy_id']);
            if (! $tenancy->rent_due_day && ! empty($validated['due_date'])) {
                $tenancy->rent_due_day = min(28, max(1, \Carbon\Carbon::parse($validated['due_date'])->day));
                $tenancy->save();
            }
            $nextStart = ! empty($validated['period_end'])
                ? \Carbon\Carbon::parse($validated['period_end'])->addDay()
                : \Carbon\Carbon::parse($validated['issue_date'])->addMonthNoOverflow();
            $finance->enableAutoInvoice($tenancy, (int) $validated['tenant_user_id'], $nextStart);
            flash('Rent invoice '.$invoice->invoice_no.' issued. Automatic invoices are on for the next periods.')->success();
        } else {
            flash('Rent invoice '.$invoice->invoice_no.' issued.')->success();
        }

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
