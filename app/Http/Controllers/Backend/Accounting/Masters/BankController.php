<?php

namespace App\Http\Controllers\Backend\Accounting\Masters;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysBankAccount;
use Illuminate\Validation\Rule;

class BankController extends BaseCrudController
{
    protected string $modelClass = SysBankAccount::class;
    protected string $viewPath = 'backend.accounting.masters.banks';
    protected string $routeName = 'backend.accounting.masters.banks';
    protected string $title = 'Banks';
    protected array $booleanFields = ['is_active', 'is_primary'];
    protected array $defaults = ['is_active' => true, 'is_primary' => false, 'balance_type' => 'savings'];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'account_name', 'label' => 'Account Holder Name'],
        ['key' => 'account_no', 'label' => 'Account No'],
        ['key' => 'sort_code', 'label' => 'Sort Code'],
        ['key' => 'bank_name', 'label' => 'Bank'],
        ['key' => 'swift_code', 'label' => 'Swift Code'],
        ['key' => 'branch', 'label' => 'Branch'],
        ['key' => 'ifsc_code', 'label' => 'IFSC Code'],
        ['key' => 'account_type', 'label' => 'Account Type'],
        ['key' => 'purpose', 'label' => 'Purpose'],
        ['key' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'money'],
        ['key' => 'balance_type', 'label' => 'Balance Type'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean'],
        ['key' => 'is_primary', 'label' => 'Primary', 'type' => 'boolean'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'account_name', 'label' => 'Account Holder Name', 'type' => 'text'],
            ['name' => 'account_no', 'label' => 'Account No', 'type' => 'text'],
            ['name' => 'sort_code', 'label' => 'Sort Code', 'type' => 'text'],
            ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text'],
            ['name' => 'swift_code', 'label' => 'Swift Code', 'type' => 'text'],
            ['name' => 'branch', 'label' => 'Branch', 'type' => 'text'],
            ['name' => 'ifsc_code', 'label' => 'IFSC Code', 'type' => 'text'],
            ['name' => 'account_type', 'label' => 'Account Type', 'type' => 'text'],
            ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'text'],
            ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'balance_type', 'label' => 'Balance Type', 'type' => 'select', 'options' => ['savings' => 'Savings', 'current' => 'Current', 'overdraft' => 'Overdraft']],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ['name' => 'is_primary', 'label' => 'Primary', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_no' => ['nullable', 'string', 'max:255'],
            'sort_code' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'balance_type' => ['nullable', Rule::in(['savings', 'current', 'overdraft'])],
        ];
    }
}
