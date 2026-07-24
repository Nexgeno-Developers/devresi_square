<?php

namespace App\Http\Controllers\Backend\Accounting\Receipts;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysBankAccount;
use App\Models\PaymentMethod;
use App\Models\SysReceipt;
use App\Models\SysPayment;
use App\Models\SysSaleInvoice;
use App\Models\GlAccount;
use App\Models\User;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ReceiptController extends BaseCrudController
{
    protected string $modelClass = SysReceipt::class;
    protected string $viewPath = 'backend.accounting.receipts';
    protected string $routeName = 'backend.accounting.receipts';
    protected string $title = 'Receipts';
    protected array $with = ['user', 'journal'];
    protected array $defaults = ['receipt_date' => null, 'status' => 'unapplied', 'applied_amount' => 0];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'receipt_no', 'label' => 'Receipt No'],
        ['key' => 'receipt_date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'user.name', 'label' => 'Customer'],
        ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
        ['key' => 'applied_amount', 'label' => 'Applied', 'type' => 'money'],
        ['key' => 'remaining_amount', 'label' => 'Remaining', 'type' => 'money'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'gl_journal_id', 'label' => 'Journal'],
    ];

    public function index()
    {
        $query = $this->query();

        if ($companyId = request('company_id')) {
            $query->where('company_id', $companyId);
        }
        if ($userId = request('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $records = $query->orderByDesc('id')->paginate(20)->appends(request()->only('company_id', 'user_id', 'status'));

        return view($this->viewPath . '.index', [
            'title' => $this->title,
            'records' => $records,
            'columns' => $this->columns,
            'routeName' => $this->routeName,
            'filters' => [
                'company_id' => request('company_id'),
                'user_id' => request('user_id'),
                'status' => request('status'),
            ],
            'selectOptions' => $this->options(),
        ]);
    }

    public function show(int $id)
    {
        $receipt = SysReceipt::with(['journal.lines.account', 'user', 'receiptable'])->findOrFail($id);

        return view($this->viewPath . '.show', [
            'title' => 'Receipt ' . $receipt->receipt_no,
            'receipt' => $receipt,
            'routeName' => $this->routeName,
        ]);
    }

    public function pdf(int $id)
    {
        $receipt = SysReceipt::with(['journal.lines.account', 'user', 'receiptable'])->findOrFail($id);
        $pdf = Pdf::loadView(
            $this->viewPath . '.pdf',
            [
                'receipt' => $receipt,
                'title' => 'Receipt ' . $receipt->receipt_no,
            ],
            [],
            ['format' => 'A4']
        );

        return $pdf->download("receipt-{$receipt->receipt_no}.pdf");
    }

    public function create()
    {
        $defaults = $this->defaults;
        $defaults['receipt_date'] = $defaults['receipt_date'] ?? now()->toDateString();
        $defaults['receipt_no'] = $defaults['receipt_no'] ?? $this->nextReceiptNo();
        $defaults['company_id'] = $defaults['company_id'] ?? (auth()->user()->company_id ?? 1);
        $paymentMethods = PaymentMethod::select('id', 'name', 'code')->orderBy('name')->get();

        return view($this->viewPath . '.create', [
            'title' => 'Create Receipt',
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'defaults' => $defaults,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data = $this->preparePayload($request, $data);
        $data['payment_meta'] = $this->validatePaymentMeta($request, $data['payment_method_id'] ?? null);
        $data['payment_meta']['notify'] = [
            'email' => $request->boolean('notify_email'),
            'sms' => $request->boolean('notify_sms'),
            'whatsapp' => $request->boolean('notify_whatsapp'),
        ];

        // Ensure generated defaults when the streamlined form posts hidden fields
        $data['receipt_no'] = $data['receipt_no'] ?? $this->nextReceiptNo();
        $data['status'] = $data['status'] ?? 'unapplied';
        $data['applied_amount'] = $data['applied_amount'] ?? 0;
        $data['company_id'] = $data['company_id'] ?? (auth()->user()->company_id ?? null);

        // Guard: if tied to an invoice, cap amount to outstanding and sync customer
        if (($data['receiptable_type'] ?? null) === 'sale_invoice' && !empty($data['receiptable_id'])) {
            if ($invoice = SysSaleInvoice::find($data['receiptable_id'])) {
                $outstanding = $invoice->balance_amount ?? $invoice->total_amount ?? null;
                if ($outstanding === null) {
                    // fallback compute from total vs payments
                    $paid = (float) $invoice->payments()
                        ->where(function ($q) {
                            $q->whereNull('is_voided')->orWhere('is_voided', 0);
                        })
                        ->sum('amount');
                    $outstanding = max(0, (float) ($invoice->total_amount ?? 0) - $paid);
                }
                if ($outstanding > 0 && ($data['amount'] - $outstanding) > 0.0001) {
                    return back()
                        ->withInput()
                        ->withErrors(['amount' => 'Amount cannot exceed outstanding (£' . number_format($outstanding, 2) . ').']);
                }

                // Default the customer to the invoice owner when not provided
                $data['user_id'] = $data['user_id'] ?? $invoice->user_id;
            }
        }

        try {
            DB::transaction(function () use (&$data) {
                /** @var SysReceipt $receipt */
                $receipt = SysReceipt::create($data);
                $journal = app(\App\Services\Accounting\PostingService::class)->postAdvanceReceipt($receipt);
                if (!$journal) {
                    throw new \RuntimeException('Ledger posting failed for receipt.');
                }

                // If tied to a sale invoice, auto-apply this receipt as a payment against it
                if (($receipt->receiptable_type ?? '') === 'sale_invoice' && $receipt->receiptable_id) {
                    $invoice = SysSaleInvoice::lockForUpdate()->findOrFail($receipt->receiptable_id);

                    // Customer consistency
                    if ($receipt->user_id && $invoice->user_id && (int)$receipt->user_id !== (int)$invoice->user_id) {
                        throw new \RuntimeException('Customer mismatch between receipt and invoice.');
                    }

                    $amount = $receipt->amount;
                    $currentBalance = $invoice->balance_amount ?? $invoice->total_amount ?? 0;
                    $newBalance = max(0, $currentBalance - $amount);

                    $paymentNotes = "Applied from receipt {$receipt->receipt_no}";
                    if (is_array($receipt->payment_meta) && !empty($receipt->payment_meta)) {
                        $summary = $this->formatPaymentMetaSummary($receipt->payment_meta, $receipt->payment_method_id);
                        if ($summary) {
                            $paymentNotes .= ' ' . $summary;
                        }
                    }

                    $payment = SysPayment::create([
                        'user_id' => $invoice->user_id,
                        'sys_bank_account_id' => $receipt->sys_bank_account_id,
                        'payment_method_id' => $receipt->payment_method_id,
                        'payment_type' => 'income',
                        'reference_type' => 'sale_invoice',
                        'reference_id' => $invoice->id,
                        'payment_date' => $receipt->receipt_date ?? now()->toDateString(),
                        'amount' => $amount,
                        'notes' => $paymentNotes,
                        'payment_meta' => $receipt->payment_meta,
                        'source_receipt_id' => $receipt->id,
                    ]);

                    $receipt->applied_amount = ($receipt->applied_amount ?? 0) + $amount;
                    $receipt->status = $receipt->remaining_amount <= 0.0001 ? 'applied' : 'partially_applied';
                    $receipt->save();

                    $invoice->update([
                        'balance_amount' => $newBalance,
                        'status' => $newBalance > 0 ? 'partial' : 'paid',
                    ]);

                    $journalApply = app(PostingService::class)->postApplyCredit($invoice, $receipt, $amount);
                    if (!$journalApply) {
                        throw new \RuntimeException('Ledger posting failed for auto-apply credit.');
                    }
                    $payment->update(['gl_journal_id' => $journalApply->id]);
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route($this->routeName . '.index')
                ->with('error', 'Receipt creation failed: ' . $e->getMessage());
        }

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' created successfully.');
    }

    public function edit(int $id)
    {
        $item = $this->query()->findOrFail($id);
        $locked = ($item->applied_amount ?? 0) > 0;
        $paymentMethods = PaymentMethod::select('id', 'name', 'code')->orderBy('name')->get();
        $defaults = array_merge($this->defaults, [
            'receipt_date' => $item->receipt_date,
            'amount' => $item->amount,
            'company_id' => $item->company_id,
            'receipt_no' => $item->receipt_no,
            'status' => $item->status,
            'applied_amount' => $item->applied_amount,
        ]);

        return view($this->viewPath . '.edit', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'item' => $item,
            'defaults' => $defaults,
            'locked' => $locked,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $item = $this->query()->findOrFail($id);
        $locked = ($item->applied_amount ?? 0) > 0;

        if ($locked) {
            // Only notes may change
            $data = $request->validate([
                'notes' => ['nullable', 'string'],
            ]);
            $item->update([
                'notes' => $data['notes'] ?? $item->notes,
            ]);
            return redirect()->route($this->routeName . '.edit', $item->id)
                ->with('success', 'Notes updated. Financial fields are locked because this receipt is applied.');
        }

        $data = $request->validate($this->rules($id));
        $data = $this->preparePayload($request, $data);
        $data['payment_meta'] = $this->validatePaymentMeta($request, $data['payment_method_id'] ?? null);
        $data['payment_meta']['notify'] = [
            'email' => $request->boolean('notify_email'),
            'sms' => $request->boolean('notify_sms'),
            'whatsapp' => $request->boolean('notify_whatsapp'),
        ];

        try {
            DB::transaction(function () use (&$data, $item) {
                $item->update($data);

                // Reverse prior journal (if any) then post fresh to reflect new amount/bank/method
                if ($item->gl_journal_id) {
                    app(PostingService::class)->reverseJournal($item->gl_journal_id, 'Receipt edit');
                }

                $journal = app(PostingService::class)->postAdvanceReceipt($item->fresh());
                if (!$journal) {
                    throw new \RuntimeException('Ledger posting failed for updated receipt.');
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route($this->routeName . '.index')
                ->with('error', 'Receipt update failed: ' . $e->getMessage());
        }

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' updated successfully.');
    }

    protected function fields(): array
    {
        return [
            ['name' => 'company_id', 'label' => 'Company ID', 'type' => 'number', 'required' => true, 'min' => '1'],
            ['name' => 'user_id', 'label' => 'Customer', 'type' => 'select', 'required' => true],
            ['name' => 'receiptable_type', 'label' => 'Receiptable Type', 'type' => 'select', 'options' => [
                'user' => 'User',
                'sale_invoice' => 'Sale Invoice',
                'purchase_invoice' => 'Purchase Invoice',
            ], 'required' => true],
            ['name' => 'receiptable_id', 'label' => 'Receiptable ID', 'type' => 'number', 'required' => true, 'min' => '1'],
            ['name' => 'receipt_no', 'label' => 'Receipt No', 'type' => 'text', 'required' => true],
            ['name' => 'receipt_date', 'label' => 'Receipt Date', 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'applied_amount', 'label' => 'Applied Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [
                'unapplied' => 'Unapplied',
                'partially_applied' => 'Partially Applied',
                'applied' => 'Applied',
            ], 'required' => true],
            ['name' => 'sys_bank_account_id', 'label' => 'Bank Account', 'type' => 'select', 'required' => true],
            ['name' => 'payment_method_id', 'label' => 'Payment Method', 'type' => 'select', 'required' => true],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function options(): array
    {
        return [
            'user_id' => User::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'sys_bank_account_id' => SysBankAccount::query()->orderBy('account_name')->pluck('account_name', 'id')->toArray(),
            'payment_method_id' => PaymentMethod::query()->orderBy('name')->pluck('name', 'id')->toArray(),
        ];
    }

    protected function rules(?int $id = null): array
    {
        $uniqueReceiptNo = Rule::unique('sys_receipts', 'receipt_no');
        if ($id) {
            $uniqueReceiptNo = $uniqueReceiptNo->ignore($id);
        }

        return [
            'company_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'exists:users,id'],
            'receiptable_type' => ['required', Rule::in(['user', 'sale_invoice', 'purchase_invoice'])],
            'receiptable_id' => ['required_with:receiptable_type', 'nullable', 'integer', 'min:1'],
            'receipt_no' => ['required', 'string', 'max:50', $uniqueReceiptNo],
            'receipt_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'applied_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['unapplied', 'partially_applied', 'applied'])],
            'sys_bank_account_id' => ['required', 'exists:sys_bank_accounts,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function defaults(): array
    {
        return ['receipt_date' => now()->toDateString(), 'status' => 'unapplied', 'applied_amount' => 0];
    }

    private function validatePaymentMeta(Request $request, ?int $paymentMethodId): array
    {
        if (!$paymentMethodId) {
            return [];
        }

        $method = PaymentMethod::find($paymentMethodId);
        $code = strtolower($method->code ?? $method->name ?? '');

        $metaRules = [];
        $labels = [];

        if (str_contains($code, 'bank')) {
            $metaRules = [
                'meta.txn_ref' => ['required', 'string', 'max:100'],
                'meta.bank_name' => ['nullable', 'string', 'max:100'],
            ];
            $labels = ['meta.txn_ref' => 'Transaction / UTR reference', 'meta.bank_name' => 'Paying bank name'];
        } elseif (str_contains($code, 'cheque') || str_contains($code, 'check')) {
            $metaRules = [
                'meta.cheque_no' => ['required', 'string', 'max:50'],
                'meta.cheque_date' => ['nullable', 'date'],
                'meta.bank_name' => ['nullable', 'string', 'max:100'],
            ];
            $labels = ['meta.cheque_no' => 'Cheque number', 'meta.cheque_date' => 'Cheque date', 'meta.bank_name' => 'Bank name'];
        } elseif (str_contains($code, 'card') || str_contains($code, 'credit')) {
            $metaRules = [
                'meta.last4' => ['required', 'digits:4'],
                'meta.auth_code' => ['required', 'string', 'max:20'],
                'meta.brand' => ['nullable', 'string', 'max:20'],
            ];
            $labels = ['meta.last4' => 'Card last 4', 'meta.auth_code' => 'Auth code', 'meta.brand' => 'Card brand'];
        } else {
            // cash or other: meta optional
            return $request->input('payment_meta', []);
        }

        $validator = \Validator::make(['meta' => $request->input('payment_meta', [])], $metaRules, [], $labels);
        $validator->validate();
        return $validator->validated()['meta'] ?? [];
    }

    private function nextReceiptNo(): string
    {
        $next = (SysReceipt::max('id') ?? 0) + 1;
        return 'RCPT-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    private function formatPaymentMetaSummary(?array $meta, ?int $paymentMethodId): string
    {
        if (!$meta || !$paymentMethodId) {
            return '';
        }
        $method = PaymentMethod::find($paymentMethodId);
        $code = strtolower($method->code ?? $method->name ?? '');

        if (str_contains($code, 'bank')) {
            return isset($meta['txn_ref']) ? "(Bank txn: {$meta['txn_ref']})" : '';
        }
        if (str_contains($code, 'cheque') || str_contains($code, 'check')) {
            return isset($meta['cheque_no']) ? "(Cheque: {$meta['cheque_no']})" : '';
        }
        if (str_contains($code, 'card') || str_contains($code, 'credit')) {
            $last4 = $meta['last4'] ?? '****';
            $auth = $meta['auth_code'] ?? '';
            return "(Card ****{$last4}" . ($auth ? " auth {$auth}" : '') . ")";
        }
        if (isset($meta['reference'])) {
            return "(Ref: {$meta['reference']})";
        }
        return '';
    }

    /**
     * Prevent deleting applied/credited receipts. Reverse ledger on delete.
     */
    public function destroy(int $id)
    {
        $receipt = SysReceipt::findOrFail($id);
        $hasCreditPayments = SysPayment::notVoided()
            ->where('source_receipt_id', $id)
            ->exists();

        $isApplied = ($receipt->applied_amount ?? 0) > 0 || ($receipt->status ?? '') !== 'unapplied' || $hasCreditPayments;
        if ($isApplied) {
            return redirect()->route($this->routeName . '.index')
                ->with('error', 'Applied receipts cannot be deleted. Undo the credit from the invoice first.');
        }

        try {
            DB::transaction(function () use ($receipt) {
                if ($receipt->gl_journal_id) {
                    $reversal = app(PostingService::class)->reverseJournal($receipt->gl_journal_id, 'Receipt delete');
                    if (!$reversal) {
                        throw new \RuntimeException('Ledger reversal could not be created. Delete aborted.');
                    }
                }

                $receipt->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->route($this->routeName . '.index')
                ->with('error', 'Receipt delete failed: ' . $e->getMessage());
        }

        return redirect()->route($this->routeName . '.index')
            ->with('success', 'Receipt deleted successfully.');
    }
}
