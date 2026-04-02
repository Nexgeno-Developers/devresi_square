<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Models\FixedAsset;
use App\Models\GlAccount;
use Illuminate\Validation\Rule;

class FixedAssetController extends BaseCrudController
{
    protected string $modelClass = FixedAsset::class;
    protected string $viewPath = 'backend.accounting.shared';
    protected string $routeName = 'backend.accounting.fixed_assets';
    protected string $title = 'Fixed Assets';
    protected array $defaults = ['status' => 'active', 'depreciation_method' => 'straight_line'];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'asset_code', 'label' => 'Code'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'category', 'label' => 'Category'],
        ['key' => 'purchase_date', 'label' => 'Purchase Date', 'type' => 'date'],
        ['key' => 'purchase_cost', 'label' => 'Cost', 'type' => 'money'],
        ['key' => 'accumulated_depreciation', 'label' => 'Accum. Depr.', 'type' => 'money'],
        ['key' => 'net_book_value', 'label' => 'NBV', 'type' => 'money'],
        ['key' => 'status', 'label' => 'Status'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'asset_code', 'label' => 'Asset Code', 'type' => 'text'],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'category', 'label' => 'Category', 'type' => 'text'],
            ['name' => 'purchase_date', 'label' => 'Purchase Date', 'type' => 'date', 'required' => true],
            ['name' => 'purchase_cost', 'label' => 'Purchase Cost', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'salvage_value', 'label' => 'Salvage Value', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'useful_life_months', 'label' => 'Useful Life (months)', 'type' => 'number', 'min' => '1', 'required' => true],
            ['name' => 'depreciation_method', 'label' => 'Depreciation Method', 'type' => 'select', 'options' => ['straight_line' => 'Straight Line']],
            ['name' => 'accumulated_depreciation', 'label' => 'Accumulated Depreciation', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'net_book_value', 'label' => 'Net Book Value', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'disposed' => 'Disposed', 'fully_depreciated' => 'Fully Depreciated']],
            ['name' => 'disposal_date', 'label' => 'Disposal Date', 'type' => 'date'],
            ['name' => 'disposal_amount', 'label' => 'Disposal Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'gl_asset_account_id', 'label' => 'GL Asset Account', 'type' => 'select'],
            ['name' => 'gl_depreciation_account_id', 'label' => 'GL Depreciation Account', 'type' => 'select'],
            ['name' => 'gl_expense_account_id', 'label' => 'GL Expense Account', 'type' => 'select'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function options(): array
    {
        $accounts = GlAccount::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' - ' . $a->name]);

        return [
            'gl_asset_account_id' => $accounts,
            'gl_depreciation_account_id' => $accounts,
            'gl_expense_account_id' => $accounts,
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'asset_code' => ['nullable', 'string', 'max:50', Rule::unique('fixed_assets', 'asset_code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['nullable', Rule::in(['straight_line'])],
            'accumulated_depreciation' => ['nullable', 'numeric', 'min:0'],
            'net_book_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'disposed', 'fully_depreciated'])],
            'disposal_date' => ['nullable', 'date'],
            'disposal_amount' => ['nullable', 'numeric', 'min:0'],
            'gl_asset_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'gl_depreciation_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'gl_expense_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
