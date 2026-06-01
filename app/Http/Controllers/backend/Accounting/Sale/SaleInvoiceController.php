<?php

namespace App\Http\Controllers\Backend\Accounting\Sale;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\SysInvoiceHeader;
use App\Models\SysSaleInvoice;
use App\Models\SysSaleInvoiceItem;
use App\Models\SysTax;
use App\Models\User;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\BankAccount;
use App\Models\OwnerGroup;
use App\Models\BusinessSetting;
use App\Models\SysReceipt;
use App\Models\SysBankAccount;
use App\Models\PaymentMethod;
use App\Models\SysPayment;
use App\Models\GlJournal;
use App\Models\GlAccount;
use App\Models\NotificationLog;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\SaleInvoiceLifecycleService;
use App\Services\Accounting\SaleInvoicePenaltyService;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class SaleInvoiceController extends BaseCrudController
{
    protected string $modelClass = SysSaleInvoice::class;
    protected string $viewPath = 'backend.accounting.sale.invoices';
    protected string $routeName = 'backend.accounting.sale.invoices';
    protected string $title = 'Sale Invoices';
    protected array $defaults = ['status' => 'draft', 'invoice_date' => null];
    protected array $with = ['items', 'user.creditReceipts', 'invoiceHeader', 'linkTo', 'chargeTo', 'bankAccount'];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'invoice_no', 'label' => 'Invoice No'],
        ['key' => 'invoiceHeader.header_name', 'label' => 'Invoice Header'],
        ['key' => 'user_id', 'label' => 'Customer'],
        ['key' => 'customer_available_credit', 'label' => 'Customer Credit', 'type' => 'money'],
        ['key' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date'],
        ['key' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
        ['key' => 'reminder_days_before_due', 'label' => 'Reminder Days'],
        ['key' => 'total_amount', 'label' => 'Total', 'type' => 'money'],
        ['key' => 'balance_amount', 'label' => 'Balance', 'type' => 'money'],
        ['key' => 'status', 'label' => 'Status'],
    ];

    public function create()
    {
        $defaults = array_merge($this->defaults, [
            'invoice_no' => $this->nextInvoiceNo(),
        ]);

        return view($this->viewPath . '.create', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'defaults' => $defaults,
            'selectedInvoiceHeader' => $this->resolveSelectedInvoiceHeader(),
        ]);
    }

    public function edit(int $id)
    {
        $item = $this->query()->findOrFail($id);

        return view($this->viewPath . '.edit', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'item' => $item,
            'defaults' => $this->defaults,
            'selectedInvoiceHeader' => $this->resolveSelectedInvoiceHeader($item),
        ]);
    }

    protected function fields(): array
    {
        return [
            ['name' => 'invoice_header_id', 'label' => 'Invoice Header', 'type' => 'select'],
            ['name' => 'link_to_type', 'label' => 'Link To Type', 'type' => 'select'],
            ['name' => 'link_to_id', 'label' => 'Link To', 'type' => 'select'],
            ['name' => 'charge_to_type', 'label' => 'Charge To Type', 'type' => 'select', 'required' => true],
            ['name' => 'charge_to_id', 'label' => 'Charge To', 'type' => 'select', 'required' => true],
            ['name' => 'bank_account_id', 'label' => 'Bank Account', 'type' => 'select'],
            ['name' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date', 'required' => true],
            ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
            ['name' => 'reminder_days_before_due', 'label' => 'Reminder Days Before Due', 'type' => 'number', 'min' => '0', 'max' => '365'],
            ['name' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'balance_amount', 'label' => 'Balance Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'issued' => 'Issued', 'paid' => 'Paid', 'partial' => 'Partial', 'cancelled' => 'Cancelled']],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        $uniqueInvoiceNo = Rule::unique('sys_sale_invoices', 'invoice_no');
        if ($id) {
            $uniqueInvoiceNo = $uniqueInvoiceNo->ignore($id);
        }

        return [
            'invoice_header_id' => ['nullable', 'exists:sys_invoice_headers,id'],
            'user_id' => ['required', 'exists:users,id'],
            'link_to_type' => ['nullable', Rule::in(SysSaleInvoice::LINK_TO_TYPES)],
            'link_to_id' => ['nullable', 'integer'],
            'charge_to_type' => ['required', Rule::in(SysSaleInvoice::CHARGE_TO_TYPES)],
            'charge_to_id' => ['required', 'integer', 'exists:users,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'invoice_no' => ['required', 'string', 'max:50', $uniqueInvoiceNo],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'reminder_days_before_due' => ['nullable', 'integer', 'min:0', 'max:365'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'balance_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'issued', 'paid', 'partial', 'cancelled'])],
            'notes' => ['nullable', 'string'],

            // Late-payment penalty settings
            'penalty_enabled' => ['nullable', 'boolean'],
            'penalty_type' => [
                'nullable',
                Rule::in(['percentage', 'flat_rate']),
                'required_if:penalty_enabled,1',
                'required_if:penalty_enabled,true',
            ],
            'penalty_fixed_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:penalty_enabled,1',
                'required_if:penalty_enabled,true',
            ],
            // Note: penalty_amount_input is intentionally not validated here anymore.
            // The UI no longer exposes it; penalty calculation ignores it.
            'penalty_gl_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'penalty_grace_days' => ['nullable', 'integer', 'min:0'],
            'penalty_max_amount' => ['nullable', 'numeric', 'min:0'],

            // Recurring schedule inputs (normalized into sys_sale_invoices columns before persisting)
            'recurring' => ['required', 'string', Rule::in(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', 'custom'])],
            'unlimited_cycles' => ['nullable', 'boolean'],
            'recurring_cycles' => ['nullable', 'integer', 'min:1'],
            'repeat_every_custom' => ['nullable', 'integer', 'min:1', 'required_if:recurring,custom'],
            'repeat_type_custom' => ['nullable', 'string', Rule::in(['day', 'week', 'month', 'year']), 'required_if:recurring,custom'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'exists:sys_taxes,id'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function options(): array
    {
        $taxes = SysTax::orderBy('name')->get(['id', 'name', 'rate']);

        return [
            'user_id' => User::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'link_to_type' => collect(SysSaleInvoice::LINK_TO_TYPES)->mapWithKeys(fn (string $type) => [$type => $type])->toArray(),
            'charge_to_type' => collect(SysSaleInvoice::CHARGE_TO_TYPES)->mapWithKeys(fn (string $type) => [$type => $type])->toArray(),
            'link_to_property' => $this->propertyOptions(),
            'link_to_tenancy' => $this->tenancyOptions(),
            'link_to_contractor' => $this->roleUserOptions(['contractor']),
            'charge_to_owner' => $this->roleUserOptions(['landlord']),
            'charge_to_tenant' => $this->roleUserOptions(['tenant']),
            'charge_to_contractor' => $this->roleUserOptions(['contractor']),
            'bank_account_id' => $this->bankAccountOptions(),
            'penalty_gl_account_id' => GlAccount::query()
                ->where('is_active', true)
                ->where('type', 'income')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray(),
            'tax_id' => $taxes->pluck('name', 'id')->toArray(),
            'tax_rates' => $taxes->pluck('rate', 'id')->toArray(),
            'bank_id' => SysBankAccount::pluck('account_name', 'id')->toArray(),
            'payment_method_id' => PaymentMethod::pluck('name', 'id')->toArray(),
        ];
    }

    public function index()
    {
        $records = $this->query()->orderByDesc('id')->paginate(20);
        $options = $this->options();

        return view($this->viewPath . '.index', [
            'title' => $this->title,
            'records' => $records,
            'columns' => $this->columns,
            'routeName' => $this->routeName,
            'bankOptions' => $options['bank_id'] ?? [],
            'paymentOptions' => $options['payment_method_id'] ?? [],
            'userOptions' => $options['user_id'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        // `invoice_no` and `user_id` are derived server-side from controller logic / `charge_to_*`.
        // The UI does not submit them anymore.
        if (! $request->filled('invoice_no')) {
            $request->merge(['invoice_no' => $this->nextInvoiceNo()]);
        }

        if (! $request->filled('user_id')) {
            $request->merge(['user_id' => $request->input('charge_to_id')]);
        }

        if (! $request->filled('recurring')) {
            $request->merge(['recurring' => '0']);
        }

        // `penalty_enabled` is a checkbox; when unchecked it won't be submitted.
        if (! $request->filled('penalty_enabled')) {
            $request->merge(['penalty_enabled' => 0]);
        }
        $request->merge(['penalty_enabled' => $request->boolean('penalty_enabled') ? 1 : 0]);

        $validator = Validator::make($request->all(), $this->rules());
        $validator->after(function ($validator) use ($request) {
            $recurring = (string) $request->input('recurring', '0');
            $unlimited = (bool) $request->boolean('unlimited_cycles');

            if ($recurring === '0') {
                if ($unlimited) {
                    $validator->errors()->add('unlimited_cycles', 'Unlimited cycles can only be enabled when recurring is set.');
                }
                return;
            }

            // When recurring is enabled and not unlimited, recurring_cycles is required.
            if (! $unlimited) {
                $cycles = $request->input('recurring_cycles');
                if ($cycles === null || $cycles === '') {
                    $validator->errors()->add('recurring_cycles', 'Cycles are required when recurring is enabled and unlimited cycles is not checked.');
                }
            }
        });

        $data = $validator->validate();
        $data = $this->preparePayload($request, $data);
        if (empty($data['invoice_no'])) {
            $data['invoice_no'] = $this->nextInvoiceNo();
        }

        // Normalize recurring UI inputs into sys_sale_invoices columns.
        $recurringMode = (string) ($data['recurring'] ?? '0');
        $unlimited = (bool) ($data['unlimited_cycles'] ?? false);

        if ($recurringMode === '0') {
            $data['recurring_master_invoice_id'] = null;
            $data['recurring_sequence'] = null;
            $data['recurring_month_interval'] = null;
            $data['recurring_custom_interval'] = null;
            $data['recurring_custom_unit'] = null;
            $data['unlimited_cycles'] = false;
            $data['recurring_cycles'] = null;
        } else {
            $data['recurring_master_invoice_id'] = null; // master/template invoice
            $data['recurring_sequence'] = 1;
            $data['recurring_month_interval'] = null;
            $data['recurring_custom_interval'] = null;
            $data['recurring_custom_unit'] = null;

            if ($recurringMode === 'custom') {
                $data['recurring_custom_interval'] = (int) ($data['repeat_every_custom'] ?? 1);
                $data['recurring_custom_unit'] = $data['repeat_type_custom'] ?? 'month';
            } else {
                // recurringMode is 1..12 (string)
                $data['recurring_month_interval'] = (int) $recurringMode;
            }

            $data['unlimited_cycles'] = $unlimited;
            $data['recurring_cycles'] = $unlimited ? null : (int) ($data['recurring_cycles'] ?? 0);
        }

        // Remove UI-only fields so persistItems won't try to write them to DB columns.
        unset($data['recurring'], $data['repeat_every_custom'], $data['repeat_type_custom']);

        $invoice = $this->persistItems($data, null);

        $penaltyService = app(SaleInvoicePenaltyService::class);
        $penaltyService->applyPenaltyIfEligible($invoice);
        $invoice->refresh();

        $this->postInvoiceIfNeeded($invoice);
        $this->sendInvoiceEmailOnCreation($invoice);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $invoice = SysSaleInvoice::findOrFail($id);

        $isChildRecurring = !empty($invoice->recurring_master_invoice_id);
        if ($isChildRecurring) {
            // Disabled recurring inputs on the child invoice won't be submitted,
            // but validation still expects these fields. Merge from DB.
            $recurringMode = !empty($invoice->recurring_month_interval)
                ? (string) $invoice->recurring_month_interval
                : (!empty($invoice->recurring_custom_unit) ? 'custom' : '0');

            $unlimited = (bool) ($invoice->unlimited_cycles ?? false);
            $cycles = $invoice->recurring_cycles;
            if (! $unlimited && ($cycles === null || $cycles === '')) {
                $cycles = 2; // safe default for legacy/partial records
            }

            $request->merge([
                'recurring' => $recurringMode,
                'unlimited_cycles' => $unlimited,
                'recurring_cycles' => $cycles,
                'repeat_every_custom' => $invoice->recurring_custom_interval,
                'repeat_type_custom' => $invoice->recurring_custom_unit,
            ]);
        }

        if (! $request->filled('invoice_no')) {
            $request->merge(['invoice_no' => $invoice->invoice_no ?? $this->nextInvoiceNo()]);
        }

        if (
            ! $request->filled('charge_to_id')
            && $request->input('charge_to_type') === $invoice->charge_to_type
            && ! empty($invoice->charge_to_id)
        ) {
            $request->merge(['charge_to_id' => $invoice->charge_to_id]);
        }

        if (! $request->filled('user_id')) {
            $request->merge(['user_id' => $request->input('charge_to_id') ?: $invoice->user_id]);
        }

        if (! $request->filled('recurring')) {
            $request->merge(['recurring' => '0']);
        }

        // `penalty_enabled` is a checkbox; when a penalty is already applied,
        // the UI disables penalty inputs and won't submit them.
        $penaltyAlreadyApplied = !empty($invoice->penalty_applied_at);
        if ($penaltyAlreadyApplied) {
            $request->merge([
                'penalty_enabled' => $invoice->penalty_enabled ? 1 : 0,
                'penalty_type' => $invoice->penalty_type,
                'penalty_fixed_rate' => $invoice->penalty_fixed_rate,
                'penalty_gl_account_id' => $invoice->penalty_gl_account_id,
                'penalty_grace_days' => $invoice->penalty_grace_days,
                'penalty_max_amount' => $invoice->penalty_max_amount,
            ]);
        } else {
            if (! $request->filled('penalty_enabled')) {
                $request->merge(['penalty_enabled' => 0]);
            }
            $request->merge(['penalty_enabled' => $request->boolean('penalty_enabled') ? 1 : 0]);
        }

        $validator = Validator::make($request->all(), $this->rules($id));
        $validator->after(function ($validator) use ($request, $invoice, $isChildRecurring) {
            $recurring = (string) $request->input('recurring', '0');
            $unlimited = (bool) $request->boolean('unlimited_cycles');

            if ($recurring === '0') {
                if ($unlimited) {
                    $validator->errors()->add('unlimited_cycles', 'Unlimited cycles can only be enabled when recurring is set.');
                }
                return;
            }

            if (! $unlimited) {
                $cycles = $request->input('recurring_cycles');
                if ($cycles === null || $cycles === '') {
                    $validator->errors()->add('recurring_cycles', 'Cycles are required when recurring is enabled and unlimited cycles is not checked.');
                }
            }

            // If editing a master invoice, prevent reducing `cycles` below what has already been generated.
            // Example: existing max child sequence is 3, but user tries to reduce cycles to 2 => reject.
            if (! $isChildRecurring && $recurring !== '0' && ! $unlimited) {
                $newCyclesTotal = (int) ($request->input('recurring_cycles') ?? 0);

                if ($newCyclesTotal > 0) {
                    $masterSeq = (int) ($invoice->recurring_sequence ?? 1);
                    $targetMaxSeq = $masterSeq + $newCyclesTotal;

                    $existingMaxSeq = (int) (SysSaleInvoice::query()
                        ->where('recurring_master_invoice_id', $invoice->id)
                        ->max('recurring_sequence') ?? 0);

                    if ($existingMaxSeq > 0 && $existingMaxSeq > $targetMaxSeq) {
                        $minCyclesTotal = $existingMaxSeq - $masterSeq;
                        $minCyclesTotal = max(1, $minCyclesTotal);

                        $validator->errors()->add(
                            'recurring_cycles',
                            "Cannot reduce cycles below {$minCyclesTotal} because invoices already exist up to sequence {$existingMaxSeq}."
                        );
                    }
                }
            }
        });

        $data = $validator->validate();
        $data = $this->preparePayload($request, $data);
        if (empty($data['invoice_no'])) {
            $data['invoice_no'] = $invoice->invoice_no ?? $this->nextInvoiceNo();
        }

        // Normalize recurring UI inputs into sys_sale_invoices columns.
        $recurringMode = (string) ($data['recurring'] ?? '0');
        $unlimited = (bool) ($data['unlimited_cycles'] ?? false);

        if (! $isChildRecurring) {
            if ($recurringMode === '0') {
                $data['recurring_master_invoice_id'] = null;
                $data['recurring_sequence'] = null;
                $data['recurring_month_interval'] = null;
                $data['recurring_custom_interval'] = null;
                $data['recurring_custom_unit'] = null;
                $data['unlimited_cycles'] = false;
                $data['recurring_cycles'] = null;
            } else {
                $data['recurring_master_invoice_id'] = null; // master/template invoice
                $data['recurring_sequence'] = 1;
                $data['recurring_month_interval'] = null;
                $data['recurring_custom_interval'] = null;
                $data['recurring_custom_unit'] = null;

                if ($recurringMode === 'custom') {
                    $data['recurring_custom_interval'] = (int) ($data['repeat_every_custom'] ?? 1);
                    $data['recurring_custom_unit'] = $data['repeat_type_custom'] ?? 'month';
                } else {
                    $data['recurring_month_interval'] = (int) $recurringMode;
                }

                $data['unlimited_cycles'] = $unlimited;
                $data['recurring_cycles'] = $unlimited ? null : (int) ($data['recurring_cycles'] ?? 0);
            }
        } else {
            // For child invoices: do not allow edits to recurrence settings.
            $data['unlimited_cycles'] = (bool) ($invoice->unlimited_cycles ?? false);
            $data['recurring_cycles'] = $invoice->recurring_cycles;
        }

        // Remove UI-only fields so persistItems won't try to write them to DB columns.
        unset($data['recurring'], $data['repeat_every_custom'], $data['repeat_type_custom']);

        $invoice = $this->persistItems($data, $invoice);

        $penaltyService = app(SaleInvoicePenaltyService::class);
        if ($penaltyAlreadyApplied) {
            $penaltyService->reconcileAppliedPenaltyStatus($invoice);
        } else {
            $penaltyService->applyPenaltyIfEligible($invoice);
        }
        $invoice->refresh();

        $this->postInvoiceIfNeeded($invoice);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' updated successfully.');
    }

    private function persistItems(array &$data, ?SysSaleInvoice $invoice): SysSaleInvoice
    {
        return app(SaleInvoiceLifecycleService::class)->persistItems($data, $invoice);
    }

    private function nextInvoiceNo(): string
    {
        return app(SaleInvoiceLifecycleService::class)->nextInvoiceNo();
    }

    private function postInvoiceIfNeeded(SysSaleInvoice $invoice): void
    {
        app(SaleInvoiceLifecycleService::class)->postInvoiceIfNeeded($invoice);
    }


    public function show(int $id)
    {
        $invoice = SysSaleInvoice::with([
            'items',
            'invoiceHeader',
            'linkTo',
            'chargeTo',
            'bankAccount',
            'payments.bankAccount',
            'payments.paymentMethod',
            'receipts'
        ])->findOrFail($id);
        $customer = User::find($invoice->user_id);

        $subtotal = 0;
        $taxTotal = 0;
        foreach ($invoice->items as $row) {
            $qty = (float)($row->quantity ?? 0);
            $rate = (float)($row->rate ?? 0);
            $discount = (float)($row->discount ?? 0);
            $lineBase = max(0, ($qty * $rate) - $discount);
            $tax = (float)($row->tax_amount ?? 0);
            $subtotal += $lineBase;
            $taxTotal += $tax;
        }
        $total = $subtotal + $taxTotal;
        $paid = $invoice->payments->sum('amount');
        $balance = $invoice->balance_amount ?? max(0, $total - $paid);

        $recurringInfo = $this->buildRecurringInfo($invoice);
        $notificationInfo = $this->buildNotificationInfo($invoice);

        return view($this->viewPath . '.show', [
            'title' => 'Invoice ' . $invoice->invoice_no,
            'invoice' => $invoice,
            'customer' => $customer,
            'subtotal' => $subtotal,
            'taxTotal' => $taxTotal,
            'total' => $total,
            'paid' => $paid,
            'balance' => $balance,
            'routeName' => $this->routeName,
            'credits' => $customer?->creditReceipts ?? collect(),
            'bankOptions' => $this->options()['bank_id'] ?? [],
            'paymentOptions' => $this->options()['payment_method_id'] ?? [],
            'recurringInfo' => $recurringInfo,
            'notificationInfo' => $notificationInfo,
        ]);
    }

    private function buildRecurringInfo(SysSaleInvoice $invoice): array
    {
        $isChild = !empty($invoice->recurring_master_invoice_id);
        $master = $isChild ? SysSaleInvoice::query()->find($invoice->recurring_master_invoice_id) : $invoice;

        if (! $master) {
            return ['enabled' => false];
        }

        $enabled = !empty($master->recurring_month_interval) || !empty($master->recurring_custom_unit);
        if (! $enabled) {
            return ['enabled' => false];
        }

        $intervalLabel = null;
        if (!empty($master->recurring_month_interval)) {
            $intervalLabel = 'Every ' . (int) $master->recurring_month_interval . ' month(s)';
        } elseif (!empty($master->recurring_custom_unit)) {
            $intervalLabel = 'Every ' . (int) ($master->recurring_custom_interval ?? 1) . ' ' . $master->recurring_custom_unit . '(s)';
        }

        $unlimited = (bool) ($master->unlimited_cycles ?? false);
        $cyclesTotal = $unlimited ? null : (int) ($master->recurring_cycles ?? 0);
        if (! $unlimited && $cyclesTotal <= 0) {
            $cyclesTotal = null;
        }

        $masterSeq = (int) ($master->recurring_sequence ?? 1);
        $targetSeq = $cyclesTotal ? ($masterSeq + $cyclesTotal) : null;

        $currentSeq = (int) ($invoice->recurring_sequence ?? ($isChild ? 0 : 1));
        if (! $isChild) {
            $currentSeq = $masterSeq;
        }

        $maxChildSeq = (int) (SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $master->id)
            ->max('recurring_sequence') ?? 0);
        $maxGeneratedSeq = max($masterSeq, $maxChildSeq);

        $cyclesLeft = null;
        if ($targetSeq !== null) {
            $cyclesLeft = max(0, $targetSeq - $currentSeq);
        }

        $remainingToGenerate = null;
        if ($targetSeq !== null) {
            $remainingToGenerate = max(0, $targetSeq - $maxGeneratedSeq);
        }

        $nextSchedule = $this->nextRecurringScheduleInfo($master, $maxGeneratedSeq, $targetSeq, $unlimited);

        return [
            'enabled' => true,
            'is_child' => $isChild,
            'master_id' => (int) $master->id,
            'current_seq' => $currentSeq,
            'max_generated_seq' => $maxGeneratedSeq,
            'interval_label' => $intervalLabel,
            'unlimited' => $unlimited,
            'cycles_total' => $cyclesTotal,
            'target_seq' => $targetSeq,
            'cycles_left' => $cyclesLeft,
            'remaining_to_generate' => $remainingToGenerate,
            'next_seq' => $nextSchedule['seq'] ?? null,
            'next_invoice_date' => $nextSchedule['invoice_date'] ?? null,
            'next_due_date' => $nextSchedule['due_date'] ?? null,
        ];
    }

    private function nextRecurringScheduleInfo(SysSaleInvoice $master, int $maxGeneratedSeq, ?int $targetSeq, bool $unlimited): ?array
    {
        $masterSeq = (int) ($master->recurring_sequence ?? 1);
        $nextSeq = max($masterSeq + 1, $maxGeneratedSeq + 1);

        if (! $unlimited && $targetSeq !== null && $nextSeq > $targetSeq) {
            return null;
        }

        if (empty($master->invoice_date)) {
            return null;
        }

        $rawOffsetDays = null;
        if (!empty($master->due_date)) {
            $rawOffsetDays = \Carbon\Carbon::parse($master->invoice_date)
                ->startOfDay()
                ->diffInDays(\Carbon\Carbon::parse($master->due_date)->startOfDay(), false);
        }

        $isMonthlyRecurrence = !empty($master->recurring_month_interval)
            || (($master->recurring_custom_unit ?? null) === 'month');

        if ($isMonthlyRecurrence && !empty($master->due_date) && $rawOffsetDays !== null && (int) $rawOffsetDays > 0) {
            $monthsStep = max(1, (int) ($master->recurring_month_interval ?: ($master->recurring_custom_interval ?? 1)));
            $steps = max(1, $nextSeq - $masterSeq);
            $baseDue = \Carbon\Carbon::parse($master->due_date)->startOfDay();
            $targetDay = (int) $baseDue->day;
            $due = $this->safeMonthlyDateForRecurringInfo($baseDue, $monthsStep * $steps, $targetDay);
            $invoiceDate = $due->copy()->subDays((int) $rawOffsetDays)->startOfDay();

            return [
                'seq' => $nextSeq,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $due->toDateString(),
            ];
        }

        $unit = !empty($master->recurring_month_interval) ? 'month' : ($master->recurring_custom_unit ?: 'month');
        $value = !empty($master->recurring_month_interval)
            ? (int) $master->recurring_month_interval
            : (int) ($master->recurring_custom_interval ?? 1);
        $offsetDays = ($rawOffsetDays !== null && (int) $rawOffsetDays > 0) ? (int) $rawOffsetDays : 30;
        $steps = max(1, $nextSeq - $masterSeq);
        $invoiceDate = \Carbon\Carbon::parse($master->invoice_date)->startOfDay();
        for ($i = 0; $i < $steps; $i++) {
            $invoiceDate = match ($unit) {
                'day' => $invoiceDate->copy()->addDays(max(1, $value)),
                'week' => $invoiceDate->copy()->addWeeks(max(1, $value)),
                'year' => $invoiceDate->copy()->addYears(max(1, $value)),
                default => $invoiceDate->copy()->addMonths(max(1, $value)),
            };
        }

        return [
            'seq' => $nextSeq,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addDays($offsetDays)->toDateString(),
        ];
    }

    private function safeMonthlyDateForRecurringInfo(\Carbon\Carbon $baseDate, int $monthsToAdd, int $targetDay): \Carbon\Carbon
    {
        $monthsToAdd = max(1, $monthsToAdd);
        $targetDay = max(1, min(31, $targetDay));
        $baseYear = (int) $baseDate->year;
        $baseMonthIndex = (int) $baseDate->month - 1;
        $totalMonths = ($baseYear * 12) + $baseMonthIndex + $monthsToAdd;
        $year = intdiv($totalMonths, 12);
        $month = ($totalMonths % 12) + 1;
        $lastDay = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->day;

        return \Carbon\Carbon::create($year, $month, min($targetDay, $lastDay))->startOfDay();
    }

    private function sendInvoiceEmailOnCreation(SysSaleInvoice $invoice): void
    {
        $invoice->loadMissing('user');

        $recipient = optional($invoice->user)->email;
        if (empty($recipient)) {
            return;
        }

        $templates = EmailTemplate::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('status', 1)
            ->get();
        if ($templates->isEmpty()) {
            return;
        }

        $existing = NotificationLog::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('channel', 'email')
            ->where('notifiable_type', $invoice->getMorphClass())
            ->where('notifiable_id', $invoice->getKey())
            ->first();
        if ($existing) {
            return;
        }

        $data = [
            'invoice_id' => (string) $invoice->id,
            'invoice_no' => (string) ($invoice->invoice_no ?? $invoice->id),
            'invoice_date' => (string) ($invoice->invoice_date ?? ''),
            'due_date' => (string) ($invoice->due_date ?? ''),
            'total_amount' => (string) ($invoice->total_amount ?? ''),
            'balance_amount' => (string) ($invoice->balance_amount ?? ''),
            'customer_name' => (string) (optional($invoice->user)->name ?? ''),
            'customer_email' => (string) (optional($invoice->user)->email ?? ''),
            'invoice_view_url' => route('backend.accounting.sale.invoices.show', $invoice->id),
            'invoice_pdf_url' => route('backend.accounting.sale.invoices.pdf', $invoice->id),
            'attach_invoice_pdf' => true,
        ];

        foreach ($templates as $template) {
            $subject = $template->subject !== null ? render_template((string) $template->subject, $data) : '';
            $message = render_template((string) ($template->default_text ?? ''), $data);

            $log = NotificationLog::create([
                'identifier' => 'sale_invoice_send',
                'notifiable_type' => $invoice->getMorphClass(),
                'notifiable_id' => $invoice->getKey(),
                'channel' => 'email',
                'recipient' => $recipient,
                'subject' => $subject,
                'message' => $message,
                'payload' => $data,
                'status' => 'pending',
                'attempt' => 0,
                'max_attempts' => (int) config('notification_system.max_attempts', 3),
            ]);

            try {
                SendNotificationJob::dispatchSync($log);
            } catch (\Throwable $e) {
                Log::warning('Sale invoice email failed during invoice creation.', [
                    'invoice_id' => $invoice->id,
                    'notification_log_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildNotificationInfo(SysSaleInvoice $invoice): array
    {
        $morphType = $invoice->getMorphClass();

        $sendLog = NotificationLog::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('channel', 'email')
            ->where('notifiable_type', $morphType)
            ->where('notifiable_id', $invoice->getKey())
            ->orderByDesc('id')
            ->first();

        $reminderLog = NotificationLog::query()
            ->where('identifier', 'sale_invoice_due_reminder')
            ->where('channel', 'email')
            ->where('notifiable_type', $morphType)
            ->where('notifiable_id', $invoice->getKey())
            ->orderByDesc('id')
            ->first();

        $overdueLog = NotificationLog::query()
            ->where('identifier', 'sale_invoice_overdue_reminder')
            ->where('channel', 'email')
            ->where('notifiable_type', $morphType)
            ->where('notifiable_id', $invoice->getKey())
            ->orderByDesc('id')
            ->first();

        $reminderDays = $invoice->reminder_days_before_due;
        if ($reminderDays === null || $reminderDays === '') {
            $reminderDays = (int) get_setting('sale_invoice_reminder_days_before_due', 3);
        }
        $reminderDays = max(0, min(365, (int) $reminderDays));

        $reminderOn = null;
        if (!empty($invoice->due_date)) {
            $reminderOn = \Carbon\Carbon::parse($invoice->due_date)->startOfDay()->subDays($reminderDays)->toDateString();
        }

        $overdueDays = (int) get_setting('sale_invoice_overdue_days_after_due', 1);
        $overdueDays = max(0, min(365, $overdueDays));

        $overdueOn = null;
        if (!empty($invoice->due_date)) {
            $overdueOn = \Carbon\Carbon::parse($invoice->due_date)->startOfDay()->addDays($overdueDays)->toDateString();
        }

        return [
            'send' => $sendLog ? [
                'status' => $sendLog->status,
                'sent_at' => $sendLog->sent_at?->toDateTimeString(),
                'last_attempt_at' => $sendLog->last_attempt_at?->toDateTimeString(),
                'attempt' => (int) ($sendLog->attempt ?? 0),
                'max_attempts' => (int) ($sendLog->max_attempts ?? 0),
                'error' => $sendLog->error,
            ] : null,
            'reminder' => $reminderLog ? [
                'status' => $reminderLog->status,
                'sent_at' => $reminderLog->sent_at?->toDateTimeString(),
                'last_attempt_at' => $reminderLog->last_attempt_at?->toDateTimeString(),
                'attempt' => (int) ($reminderLog->attempt ?? 0),
                'max_attempts' => (int) ($reminderLog->max_attempts ?? 0),
                'error' => $reminderLog->error,
            ] : null,
            'overdue' => $overdueLog ? [
                'status' => $overdueLog->status,
                'sent_at' => $overdueLog->sent_at?->toDateTimeString(),
                'last_attempt_at' => $overdueLog->last_attempt_at?->toDateTimeString(),
                'attempt' => (int) ($overdueLog->attempt ?? 0),
                'max_attempts' => (int) ($overdueLog->max_attempts ?? 0),
                'error' => $overdueLog->error,
            ] : null,
            'reminder_days_before_due' => $reminderDays,
            'reminder_on' => $reminderOn,
            'overdue_days_after_due' => $overdueDays,
            'overdue_on' => $overdueOn,
        ];
    }

    public function pdf(int $id)
    {
        $invoice = SysSaleInvoice::with([
            'items',
            'invoiceHeader',
            'payments.bankAccount',
            'payments.paymentMethod',
        ])->findOrFail($id);

        $customer = User::find($invoice->user_id);

        $subtotal = 0;
        $taxTotal = 0;
        foreach ($invoice->items as $row) {
            $lineBase = max(0, ($row->quantity * $row->rate) - ($row->discount ?? 0));
            $subtotal += $lineBase;
            $taxTotal += (float) ($row->tax_amount ?? 0);
        }
        $total = $subtotal + $taxTotal;
        $paid  = $invoice->payments->sum('amount');
        $balance = $invoice->balance_amount ?? max(0, $total - $paid);

        $pdf = Pdf::loadView(
            $this->viewPath . '.pdf',
            compact('invoice', 'customer', 'subtotal', 'taxTotal', 'total', 'paid', 'balance'),
            [],
            ['format' => 'A4']
        );

        return $pdf->download("invoice-{$invoice->invoice_no}.pdf");
    }

    /**
     * Initiate Stripe Checkout (test/sandbox) for the invoice balance.
     */
    public function pay(Request $request, int $id)
    {
        $invoice = SysSaleInvoice::findOrFail($id);

        if (($invoice->balance_amount ?? 0) <= 0) {
            return back()->with('error', 'Invoice is already fully paid.');
        }

        $secret = config('services.stripe.test_secret') ?: env('STRIPE_TEST_SECRET');
        $publishable = config('services.stripe.test_key') ?: env('STRIPE_PUBLISHABLE_TEST');

        if (!$secret || !$publishable) {
            return back()->with('error', 'Stripe test keys are missing. Set STRIPE_TEST_SECRET and STRIPE_PUBLISHABLE_TEST in .env.');
        }

        $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.5'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $amountRequested = $request->input('amount');
        $amount = $amountRequested ? (float)$amountRequested : (float)$invoice->balance_amount;
        $amount = min($amount, (float)$invoice->balance_amount);
        if ($amount <= 0) {
            return back()->with('error', 'Payment amount must be greater than zero.');
        }

        try {
            $sessionUrl = $this->createStripeCheckoutUrl(
                $secret,
                $invoice,
                $request->input('currency', 'gbp'),
                $publishable,
                $amount
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not create Stripe session: ' . $e->getMessage());
        }

        return redirect()->away($sessionUrl);
    }

    /**
     * Minimal Stripe Checkout session creation using cURL (no SDK).
     */
    private function createStripeCheckoutUrl(string $secret, SysSaleInvoice $invoice, string $currency, string $publishable, float $amount): string
    {
        $amountCents = (int) round(max(0.5, $amount) * 100); // Stripe requires >= 50 cents equivalent
        $successUrl = URL::signedRoute('backend.accounting.sale.invoices.paid', [
            'invoice' => $invoice->id,
            'amount' => $amount,
        ]);

        $data = [
            'mode' => 'payment',
            'payment_method_types[]' => 'card',
            'success_url' => $successUrl,
            'cancel_url' => route($this->routeName . '.edit', $invoice->id),
            'customer_email' => optional($invoice->user)->email,
            'client_reference_id' => 'sale-invoice-' . $invoice->id,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => 'Invoice ' . $invoice->invoice_no,
            'line_items[0][price_data][product_data][description]' => 'Payment for invoice #' . $invoice->invoice_no,
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][quantity]' => 1,
        ];

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('Stripe API error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($status >= 400 || empty($json['url'])) {
            $message = $json['error']['message'] ?? 'Unknown error';
            throw new \RuntimeException('Stripe responded with ' . $status . ': ' . $message);
        }

        return $json['url'];
    }

    /**
     * Mark invoice paid after Stripe success (test mode).
     */
    public function markPaid(Request $request, int $id)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $invoice = SysSaleInvoice::with('payments')->findOrFail($id);

        $amount = (float) $request->query('amount', $invoice->balance_amount ?? 0);
        $statusAfter = 'paid';
        $remaining = 0;

        DB::transaction(function () use ($invoice, $amount, &$statusAfter, &$remaining) {
            $amount = max(0, $amount);
            if ($amount > 0) {
                $bankId = SysBankAccount::query()->value('id');
                $methodId = PaymentMethod::query()->value('id');

                if ($bankId && $methodId) {
                    $payment = SysPayment::create([
                        'user_id' => $invoice->user_id,
                        'sys_bank_account_id' => $bankId,
                        'payment_method_id' => $methodId,
                        'payment_type' => 'income',
                        'reference_type' => 'sale_invoice',
                        'reference_id' => $invoice->id,
                        'payment_date' => now()->toDateString(),
                        'amount' => $amount,
                        'notes' => 'Stripe test payment auto-recorded',
                    ]);
                    $journal = app(PostingService::class)->postStripePayment($invoice, $payment, 'Stripe test payment auto-recorded');
                    if ($journal) {
                        $payment->update(['gl_journal_id' => $journal->id]);
                    }
                }
            }

            $newBalance = max(0, ($invoice->balance_amount ?? 0) - $amount);
            $invoice->update([
                'status' => $newBalance > 0 ? 'partial' : 'paid',
                'balance_amount' => $newBalance,
            ]);
            $statusAfter = $newBalance > 0 ? 'partial' : 'paid';
            $remaining = $newBalance;
        });

        $msg = $statusAfter === 'paid'
            ? "Invoice {$invoice->invoice_no} marked as paid."
            : "Payment recorded for {$invoice->invoice_no}. Status: PARTIAL. Remaining balance: " . number_format($remaining, 2);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $msg);
    }

    public function storeAdvance(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.5'],
            'receipt_date' => ['required', 'date'],
            'sys_bank_account_id' => ['required', 'exists:sys_bank_accounts,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $receiptNo = $this->nextReceiptNo();

        try {
            DB::transaction(function () use ($data, $receiptNo) {
                $receipt = SysReceipt::create([
                    'company_id' => optional(Auth::user())->company_id ?? 1,
                    'user_id' => $data['user_id'],
                    'receiptable_type' => 'user',
                    'receiptable_id' => $data['user_id'],
                    'receipt_no' => $receiptNo,
                    'receipt_date' => $data['receipt_date'],
                    'amount' => $data['amount'],
                    'sys_bank_account_id' => $data['sys_bank_account_id'],
                    'payment_method_id' => $data['payment_method_id'],
                    'reference_no' => null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'unapplied',
                    'applied_amount' => 0,
                ]);

                $journal = app(PostingService::class)->postAdvanceReceipt($receipt);
                if (!$journal) {
                    throw new \RuntimeException('Ledger posting failed for advance receipt.');
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route($this->routeName . '.index')
                ->with('error', 'Advance receipt failed: ' . $e->getMessage());
        }

        return redirect()->route($this->routeName . '.index')
            ->with('success', "Advance receipt {$receiptNo} recorded.");
    }

    public function applyCredit(Request $request, int $id)
    {
        $invoice = SysSaleInvoice::with('user')->findOrFail($id);

        $data = $request->validate([
            'credits' => ['required', 'array', 'min:1'],
            'credits.*.receipt_id' => ['required', 'integer', 'exists:sys_receipts,id'],
            'credits.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoiceBalance = (float) ($invoice->balance_amount ?? 0);
        if ($invoiceBalance <= 0) {
            return back()->with('error', 'Invoice is already fully paid.');
        }

        $lines = collect($data['credits'])
            ->map(function ($row) {
                return [
                    'receipt_id' => (int) ($row['receipt_id'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['amount'] > 0);

        if ($lines->isEmpty()) {
            return back()->with('error', 'Enter at least one credit amount greater than zero.');
        }

        $totalApply = $lines->sum('amount');
        if ($totalApply - $invoiceBalance > 0.0001) {
            return back()->with('error', 'Total credit amount exceeds invoice balance.');
        }

        $bankId = SysBankAccount::query()->value('id');
        $methodId = PaymentMethod::query()->value('id');
        if (!$bankId || !$methodId) {
            return back()->with('error', 'Bank account or payment method missing.');
        }

        $newBalance = $invoiceBalance;

        try {
            DB::transaction(function () use ($invoice, $lines, $bankId, $methodId, &$newBalance) {
                foreach ($lines as $row) {
                    $receipt = SysReceipt::where('id', $row['receipt_id'])
                        ->where('receiptable_type', 'user')
                        ->where('receiptable_id', $invoice->user_id)
                        ->whereIn('status', ['unapplied', 'partially_applied'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $amount = $row['amount'];
                    $remainingCredit = $receipt->remaining_amount;

                    if ($amount - $remainingCredit > 0.0001) {
                        throw new \RuntimeException("Amount for {$receipt->receipt_no} exceeds credit available.");
                    }
                    if ($amount - $newBalance > 0.0001) {
                        throw new \RuntimeException('Total credit amount exceeds invoice balance.');
                    }

                    $paymentMeta = $receipt->payment_meta ?? null;
                    $methodIdToUse = $receipt->payment_method_id ?? $methodId;
                    $bankIdToUse = $receipt->sys_bank_account_id ?? $bankId;
                    $paymentNotes = "Applied from receipt {$receipt->receipt_no}";
                    if (is_array($paymentMeta) && !empty($paymentMeta)) {
                        $paymentNotes .= ' ' . $this->formatPaymentMetaSummary($paymentMeta, $methodIdToUse);
                    }

                    $payment = SysPayment::create([
                        'user_id' => $invoice->user_id,
                        'sys_bank_account_id' => $bankIdToUse,
                        'payment_method_id' => $methodIdToUse,
                        'payment_type' => 'income',
                        'reference_type' => 'sale_invoice',
                        'reference_id' => $invoice->id,
                        'payment_date' => now()->toDateString(),
                        'amount' => $amount,
                        'notes' => $paymentNotes,
                        'payment_meta' => $paymentMeta,
                        'source_receipt_id' => $receipt->id,
                    ]);

                    $receipt->applied_amount = ($receipt->applied_amount ?? 0) + $amount;
                    $receipt->status = $receipt->remaining_amount <= 0.0001 ? 'applied' : 'partially_applied';
                    $receipt->save();

                    $newBalance = max(0, $newBalance - $amount);
                    $invoice->update([
                        'balance_amount' => $newBalance,
                        'status' => $newBalance > 0 ? 'partial' : 'paid',
                    ]);

                    $journal = app(PostingService::class)->postApplyCredit($invoice, $receipt, $amount);
                    if (!$journal) {
                        throw new \RuntimeException('Ledger posting failed for apply credit.');
                    }
                    $payment->update(['gl_journal_id' => $journal->id]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Apply credit failed: ' . $e->getMessage());
        }

        $msg = "Applied " . number_format($totalApply, 2) . " credit(s). New balance: " . number_format($newBalance, 2);
        return redirect()->route($this->routeName . '.index')->with('success', $msg);
    }

    /**
     * AJAX search for sale invoices (Select2) to attach receipts.
     */
    public function ajaxSearchForReceipts(Request $request)
    {
        $q = $request->query('q', null);
        $onlyOutstanding = (bool) $request->query('only_outstanding', true);
        $limit = (int) $request->query('limit', 50);

        $query = SysSaleInvoice::query()
            ->select([
                'sys_sale_invoices.id',
                'sys_sale_invoices.invoice_no',
                'sys_sale_invoices.user_id',
                'sys_sale_invoices.total_amount',
                'sys_sale_invoices.balance_amount',
                'users.name as user_name',
            ])
            ->leftJoin('users', 'users.id', '=', 'sys_sale_invoices.user_id')
            ->leftJoin('sys_payments', function ($join) {
                $join->on('sys_payments.reference_id', '=', 'sys_sale_invoices.id')
                    ->where('sys_payments.reference_type', '=', 'sale_invoice')
                    ->where(function ($q) {
                        $q->whereNull('sys_payments.is_voided')->orWhere('sys_payments.is_voided', 0);
                    });
            })
            ->selectRaw('COALESCE(sys_sale_invoices.balance_amount, sys_sale_invoices.total_amount - COALESCE(SUM(sys_payments.amount), 0), sys_sale_invoices.total_amount, 0) as outstanding')
            ->groupBy('sys_sale_invoices.id', 'sys_sale_invoices.invoice_no', 'sys_sale_invoices.user_id', 'sys_sale_invoices.total_amount', 'sys_sale_invoices.balance_amount', 'users.name');

        if (!is_null($q) && $q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('sys_sale_invoices.invoice_no', 'like', "%{$q}%")
                    ->orWhere('users.name', 'like', "%{$q}%");

                if (is_numeric($q)) {
                    $w->orWhere('sys_sale_invoices.id', (int) $q);
                }
            });
        }

        if ($onlyOutstanding) {
            $query->having('outstanding', '>', 0);
        }

        $results = $query->orderByDesc('sys_sale_invoices.id')
            ->limit($limit)
            ->get()
            ->map(function ($inv) {
                // ensure numeric types for formatting
                $inv->total_amount = (float) ($inv->total_amount ?? 0);
                $inv->outstanding = isset($inv->outstanding) ? (float) $inv->outstanding : null;
                return $this->mapInvoiceForSelect($inv);
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Return a single sale invoice detail for Select2 preselect/detail fetch.
     */
    public function ajaxGetForReceipts(SysSaleInvoice $invoice)
    {
        // Reuse the mapper to guarantee identical shape
        $item = $this->mapInvoiceForSelect($invoice, true);
        return response()->json($item);
    }

    public function propertyContext(Property $property)
    {
        $property->loadMissing('countryRelation');

        $owners = OwnerGroup::query()
            ->with(['ownerGroupUsers.user:id,name,email,phone'])
            ->where('property_id', $property->id)
            ->where('status', 'active')
            ->get()
            ->flatMap(function (OwnerGroup $ownerGroup) {
                return $ownerGroup->ownerGroupUsers
                    ->filter(fn ($ownerGroupUser) => $ownerGroupUser->user)
                    ->map(function ($ownerGroupUser) {
                        $name = $ownerGroupUser->user->name ?: 'Owner #' . $ownerGroupUser->user_id;

                        return [
                            'id' => $ownerGroupUser->user_id,
                            'name' => $name,
                            'email' => $ownerGroupUser->user->email,
                            'phone' => $ownerGroupUser->user->phone,
                            'is_main' => (bool) $ownerGroupUser->is_main,
                            'label' => $name . ($ownerGroupUser->is_main ? ' (Main)' : ''),
                        ];
                    });
            })
            ->unique('id')
            ->values();

        $tenants = Tenancy::query()
            ->with(['tenantMembers.user:id,name,email,phone'])
            ->where('property_id', $property->id)
            ->whereNotIn('status', ['Archive', 'Archived'])
            ->get()
            ->flatMap(function (Tenancy $tenancy) {
                return $tenancy->tenantMembers
                    ->filter(fn ($tenantMember) => $tenantMember->user)
                    ->map(function ($tenantMember) use ($tenancy) {
                        $name = $tenantMember->user->name ?: 'Tenant #' . $tenantMember->user_id;
                        $suffix = ' - Tenancy #' . $tenancy->id;
                        if ($tenantMember->is_main_person) {
                            $suffix .= ' (Main)';
                        }

                        return [
                            'id' => $tenantMember->user_id,
                            'name' => $name,
                            'email' => $tenantMember->user->email,
                            'phone' => $tenantMember->user->phone,
                            'tenancy_id' => $tenancy->id,
                            'is_main' => (bool) $tenantMember->is_main_person,
                            'label' => $name . $suffix,
                        ];
                    });
            })
            ->unique('id')
            ->values();

        return response()->json([
            'property' => [
                'id' => $property->id,
                'name' => $property->prop_name ?: $property->line_1 ?: "Property #{$property->id}",
                'reference' => $property->prop_ref_no,
                'address' => $property->full_address ?: 'Address not available',
            ],
            'owners' => $owners,
            'tenants' => $tenants,
        ]);
    }

    public function tenancyContext(Property $property, User $tenant)
    {
        $today = \Carbon\Carbon::now()->startOfDay();
        $todayString = $today->toDateString();

        $baseQuery = Tenancy::query()
            ->with(['tenantMembers.user:id,name,email,phone'])
            ->where('property_id', $property->id)
            ->where('status', 'Active')
            ->whereHas('tenantMembers', function ($q) use ($tenant) {
                $q->where('user_id', $tenant->id);
            });

        $inRange = (clone $baseQuery)
            ->whereNotNull('move_in')
            ->whereDate('move_in', '<=', $todayString)
            ->where(function ($q) use ($todayString) {
                $q->whereNull('move_out')->orWhereDate('move_out', '>=', $todayString);
            })
            ->orderByDesc('move_in')
            ->orderByDesc('id')
            ->get();

        $tenancies = $inRange;
        if ($tenancies->isEmpty()) {
            $tenancies = $baseQuery
                ->orderByDesc('move_in')
                ->orderByDesc('id')
                ->get();
        }

        $rows = $tenancies->map(function (Tenancy $tenancy) {
            $mainMember = $tenancy->tenantMembers->firstWhere('is_main_person', true)
                ?: $tenancy->tenantMembers->first();
            $mainUser = $mainMember?->user;

            return [
                'id' => $tenancy->id,
                'move_in' => $tenancy->move_in,
                'move_out' => $tenancy->move_out,
                'rent' => $tenancy->rent,
                'frequency' => $tenancy->frequency,
                'status' => $tenancy->status,
                'main_tenant' => $mainUser ? [
                    'id' => $mainUser->id,
                    'name' => $mainUser->name,
                    'email' => $mainUser->email,
                    'phone' => $mainUser->phone,
                ] : null,
            ];
        })->values();

        return response()->json([
            'property' => [
                'id' => $property->id,
                'name' => $property->prop_name ?: $property->line_1 ?: "Property #{$property->id}",
                'reference' => $property->prop_ref_no,
                'address' => $property->full_address ?: 'Address not available',
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
            ],
            'selected_tenancy_id' => $rows->first()['id'] ?? null,
            'tenancies' => $rows,
        ]);
    }

    private function mapInvoiceForSelect(object $inv, bool $refreshOutstanding = false): array
    {
        $total = (float) ($inv->total_amount ?? 0);
        $balance = $inv->balance_amount ?? null;
        $precomputed = isset($inv->outstanding) ? (float) $inv->outstanding : null;

        if ($precomputed !== null && !$refreshOutstanding) {
            $outstanding = max(0, $precomputed);
        } elseif ($refreshOutstanding || $balance === null) {
            // If balance not stored, recompute from payments
            $paid = (float) $inv->payments()
                ->where(function ($q) {
                    $q->whereNull('is_voided')->orWhere('is_voided', 0);
                })
                ->sum('amount');
            $outstanding = max(0, $total - $paid);
        } else {
            $outstanding = max(0, (float) $balance);
        }

        return [
            'id' => $inv->id,
            'text' => "{$inv->invoice_no} — ". getPoundSymbol() . number_format($total, 2),
            'outstanding' => $outstanding,
            'user_id' => $inv->user_id,
            'user_name' => optional($inv->user)->name ?? ($inv->user_name ?? null),
            'total_amount' => $total,
        ];
    }

    private function nextReceiptNo(): string
    {
        $next = (SysReceipt::max('id') ?? 0) + 1;
        return 'RCPT-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    private function propertyOptions(): array
    {
        return Property::query()
            ->orderBy('prop_name')
            ->orderBy('line_1')
            ->get(['id', 'prop_ref_no', 'prop_name', 'line_1'])
            ->mapWithKeys(function (Property $property) {
                $labelParts = array_filter([
                    $property->prop_ref_no,
                    $property->prop_name ?: $property->line_1,
                ]);

                return [$property->id => implode(' - ', $labelParts) ?: "Property #{$property->id}"];
            })
            ->toArray();
    }

    private function tenancyOptions(): array
    {
        return Tenancy::query()
            ->with(['property:id,prop_ref_no,prop_name,line_1', 'tenantMembers.user:id,name'])
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (Tenancy $tenancy) {
                $property = $tenancy->property;
                $propertyLabel = collect([
                    $property?->prop_ref_no,
                    $property?->prop_name ?: $property?->line_1,
                ])->filter()->implode(' - ');

                $tenantNames = $tenancy->tenantMembers
                    ->pluck('user.name')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $label = "Tenancy #{$tenancy->id}";
                if ($propertyLabel !== '') {
                    $label .= " - {$propertyLabel}";
                }
                if ($tenantNames !== '') {
                    $label .= " ({$tenantNames})";
                }

                return [$tenancy->id => $label];
            })
            ->toArray();
    }

    private function roleUserOptions(array $roleNames): array
    {
        return User::query()
            ->whereHas('roles', function ($query) use ($roleNames) {
                $query->where(function ($roleQuery) use ($roleNames) {
                    foreach ($roleNames as $roleName) {
                        $roleQuery->orWhereRaw('LOWER(name) = ?', [strtolower($roleName)]);
                    }
                });
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private function bankAccountOptions(): array
    {
        return BankAccount::query()
            ->with('user:id,name')
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->orderByDesc('is_primary')
            ->orderBy('account_name')
            ->get()
            ->mapWithKeys(function (BankAccount $bankAccount) {
                $last4 = $bankAccount->account_no ? substr($bankAccount->account_no, -4) : null;
                $label = $bankAccount->account_name ?: $bankAccount->bank_name ?: "Bank #{$bankAccount->id}";

                if ($bankAccount->user?->name) {
                    $label .= " - {$bankAccount->user->name}";
                }
                if ($last4) {
                    $label .= " ({$last4})";
                }

                return [$bankAccount->id => $label];
            })
            ->toArray();
    }

    private function resolveSelectedInvoiceHeader(?SysSaleInvoice $invoice = null): ?SysInvoiceHeader
    {
        $selectedId = old('invoice_header_id');

        if ($selectedId) {
            return SysInvoiceHeader::find($selectedId);
        }

        return $invoice?->invoiceHeader;
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
     * Undo an applied credit payment.
     */
    public function undoCredit(Request $request, int $invoiceId, int $paymentId, PostingService $posting)
    {
        $invoice = SysSaleInvoice::findOrFail($invoiceId);
        $payment = SysPayment::where('id', $paymentId)
            ->where('reference_type', 'sale_invoice')
            ->where('reference_id', $invoiceId)
            ->firstOrFail();

        if ($payment->is_voided) {
            return back()->with('error', 'This payment was already voided.');
        }
        if (!$payment->source_receipt_id) {
            return back()->with('error', 'Only payments sourced from credits can be undone.');
        }

        $receipt = SysReceipt::findOrFail($payment->source_receipt_id);

        // customer consistency guard
        if (($payment->user_id && $payment->user_id !== $invoice->user_id) || $receipt->user_id !== $invoice->user_id) {
            return back()->with('error', 'Customer mismatch. Undo aborted.');
        }

        // prevent over-restoration of invoice balance
        $projectedBalance = ($invoice->balance_amount ?? 0) + $payment->amount;
        if ($invoice->total_amount !== null && $projectedBalance - $invoice->total_amount > 0.0001) {
            return back()->with('error', 'Undo would exceed invoice total. Nothing changed.');
        }

        try {
            DB::transaction(function () use ($payment, $receipt, $invoice, $posting) {
                // Prefer reversing original journal; if missing, post compensating entry
                if ($payment->gl_journal_id) {
                    $reversal = $posting->reverseJournal($payment->gl_journal_id, 'Undo Credit');
                } else {
                    $reversal = $posting->postUndoCredit($invoice, $receipt, $payment->amount);
                }

                if (!$reversal) {
                    throw new \RuntimeException('Reversal journal could not be created.');
                }
                // rollback receipt
                $receipt->applied_amount = max(0, ($receipt->applied_amount ?? 0) - $payment->amount);
                $applied = $receipt->applied_amount ?? 0;
                if ($applied <= 0.0001) {
                    $receipt->status = 'unapplied';
                } elseif ($applied < $receipt->amount) {
                    $receipt->status = 'partially_applied';
                } else {
                    $receipt->status = 'applied';
                }
                $receipt->save();

                // rollback invoice
                $newBalance = ($invoice->balance_amount ?? 0) + $payment->amount;
                $status = 'issued';
                if (abs($newBalance) <= 0.0001) {
                    $status = 'paid';
                } elseif ($newBalance < ($invoice->total_amount ?? $newBalance)) {
                    $status = 'partial';
                }
                $invoice->update([
                    'balance_amount' => $newBalance,
                    'status' => $status,
                ]);

                $payment->update([
                    'is_voided' => true,
                    'voided_at' => now(),
                    'notes' => trim(($payment->notes ? $payment->notes . ' ' : '') . '(voided: undo credit)'),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Undo failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Credit application undone. Reversal journal created and payment voided.');
    }
}
