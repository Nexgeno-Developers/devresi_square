<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Models\GlAccount;
use Illuminate\Validation\Rule;

class GlAccountController extends BaseCrudController
{
    protected string $modelClass = GlAccount::class;
    protected string $viewPath = 'backend.accounting.shared';
    protected string $routeName = 'backend.accounting.gl_accounts';
    protected string $title = 'GL Accounts';
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'type', 'label' => 'Type'],
        ['key' => 'group', 'label' => 'Group'],
        ['key' => 'parent.name', 'label' => 'Parent'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean'],
    ];
    protected array $booleanFields = ['is_active'];
    protected array $defaults = ['is_active' => true];
    protected array $with = ['parent'];

    protected function fields(): array
    {
        return [
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            [
                'name' => 'type',
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'asset' => 'Asset',
                    'liability' => 'Liability',
                    'income' => 'Income',
                    'expense' => 'Expense',
                    'equity' => 'Equity',
                ],
                'required' => true,
            ],
            ['name' => 'parent_id', 'label' => 'Parent Account', 'type' => 'select'],
            [
                'name' => 'group',
                'label' => 'Group',
                'type' => 'select',
                'options' => [
                    '' => '— None —',
                    'Current Assets' => 'Current Assets',
                    'Fixed Assets' => 'Fixed Assets',
                    'Current Liabilities' => 'Current Liabilities',
                    'Long-term Liabilities' => 'Long-term Liabilities',
                    'Operating Income' => 'Operating Income',
                    'Other Income' => 'Other Income',
                    'Operating Expenses' => 'Operating Expenses',
                    'Other Expenses' => 'Other Expenses',
                    'Owner Equity' => 'Owner Equity',
                ],
            ],
            ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number', 'min' => '0'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function options(): array
    {
        return [
            'parent_id' => GlAccount::where('is_active', true)
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' - ' . $a->name]),
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('gl_accounts', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'income', 'expense', 'equity'])],
            'parent_id' => ['nullable', 'exists:gl_accounts,id'],
            'group' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
