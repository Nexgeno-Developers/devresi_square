<?php

namespace App\Http\Controllers\Backend\Accounting\Payments;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\PaymentMethod;
use App\Models\SysBankAccount;
use App\Models\SysPayment;
use App\Models\User;
use Illuminate\Validation\Rule;

class PaymentController extends BaseCrudController
{
    protected string $modelClass = SysPayment::class;
    protected string $viewPath = 'backend.accounting.payments';
    protected string $routeName = 'backend.accounting.payments';
    protected string $title = 'Payments';
    protected array $defaults = ['payment_type' => 'income', 'payment_date' => null];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'payment_date', 'label' => 'Payment Date', 'type' => 'date'],
        ['key' => 'payment_type', 'label' => 'Payment Type'],
        ['key' => 'user_id', 'label' => 'User'],
        ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
        ['key' => 'reference_type', 'label' => 'Reference Type'],
        ['key' => 'reference_id', 'label' => 'Reference ID'],
    ];

    public function index()
    {
        return $this->all();
    }

    public function all()
    {
        return $this->filteredList(null, 'All Transactions');
    }

    public function incomes()
    {
        return $this->filteredList('income', 'Incomes (Deposit)');
    }

    public function expenses()
    {
        return $this->filteredList('expense', 'Expenses');
    }

    public function general()
    {
        return $this->filteredList('general', 'General Entry');
    }

    public function create()
    {
        $defaults = $this->defaults;
        $type = request()->query('payment_type');
        if (in_array($type, ['income', 'expense', 'general'], true)) {
            $defaults['payment_type'] = $type;
        }

        return view($this->viewPath . '.create', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'defaults' => $defaults,
        ]);
    }

    protected function fields(): array
    {
        return [
            ['name' => 'user_id', 'label' => 'User', 'type' => 'select'],
            ['name' => 'sys_bank_account_id', 'label' => 'Bank Account', 'type' => 'select', 'required' => true],
            ['name' => 'payment_method_id', 'label' => 'Payment Method', 'type' => 'select', 'required' => true],
            ['name' => 'payment_type', 'label' => 'Payment Type', 'type' => 'select', 'options' => ['income' => 'Income (Deposit)', 'expense' => 'Expense', 'general' => 'General'], 'required' => true],
            ['name' => 'reference_type', 'label' => 'Reference Type', 'type' => 'select', 'options' => ['sale_invoice' => 'Sale Invoice', 'purchase_invoice' => 'Purchase Invoice']],
            ['name' => 'reference_id', 'label' => 'Reference ID', 'type' => 'number', 'min' => '1'],
            ['name' => 'payment_date', 'label' => 'Payment Date', 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
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
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'sys_bank_account_id' => ['required', 'exists:sys_bank_accounts,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_type' => ['required', Rule::in(['income', 'expense', 'general'])],
            'reference_type' => ['nullable', Rule::in(['sale_invoice', 'purchase_invoice'])],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function filteredList(?string $type, string $title)
    {
        $query = $this->query();
        if ($type) {
            $query->where('payment_type', $type);
        }

        $records = $query->orderByDesc('id')->paginate(20);
        $createUrl = route($this->routeName . '.create', $type ? ['payment_type' => $type] : []);

        return view($this->viewPath . '.index', [
            'title' => $title,
            'records' => $records,
            'columns' => $this->columns,
            'routeName' => $this->routeName,
            'createUrl' => $createUrl,
        ]);
    }
}
