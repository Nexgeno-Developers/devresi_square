<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Models\GlAccount;
use App\Models\GlAccountBalance;
use Illuminate\Validation\Rule;

class GlAccountBalanceController extends BaseCrudController
{
    protected string $modelClass = GlAccountBalance::class;
    protected string $viewPath = 'backend.accounting.shared';
    protected string $routeName = 'backend.accounting.gl_account_balances';
    protected string $title = 'GL Account Balances';
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'account.code', 'label' => 'Account Code'],
        ['key' => 'account.name', 'label' => 'Account Name'],
        ['key' => 'period', 'label' => 'Period'],
        ['key' => 'debit', 'label' => 'Debit', 'type' => 'money'],
        ['key' => 'credit', 'label' => 'Credit', 'type' => 'money'],
    ];
    protected array $with = ['account'];

    protected function fields(): array
    {
        return [
            ['name' => 'gl_account_id', 'label' => 'Account', 'type' => 'select', 'required' => true],
            ['name' => 'period', 'label' => 'Period (YYYY-MM)', 'type' => 'month', 'required' => true],
            ['name' => 'debit', 'label' => 'Debit', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'credit', 'label' => 'Credit', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
        ];
    }

    protected function options(): array
    {
        $accounts = GlAccount::orderBy('code')->get()->mapWithKeys(function ($account) {
            return [$account->id => $account->code . ' - ' . $account->name];
        });

        return [
            'gl_account_id' => $accounts,
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'gl_account_id' => ['required', 'exists:gl_accounts,id'],
            'period' => [
                'required',
                'date_format:Y-m',
                Rule::unique('gl_account_balances', 'period')
                    ->where(fn ($query) => $query->where('gl_account_id', request('gl_account_id')))
                    ->ignore($id),
            ],
            'debit' => ['required', 'numeric', 'min:0'],
            'credit' => ['required', 'numeric', 'min:0'],
        ];
    }
}
