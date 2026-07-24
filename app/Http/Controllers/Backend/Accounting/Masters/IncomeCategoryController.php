<?php

namespace App\Http\Controllers\Backend\Accounting\Masters;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysIncomeCategory;

class IncomeCategoryController extends BaseCrudController
{
    protected string $modelClass = SysIncomeCategory::class;
    protected string $viewPath = 'backend.accounting.masters.income_categories';
    protected string $routeName = 'backend.accounting.masters.income_categories';
    protected string $title = 'Income Categories';
    protected array $booleanFields = ['is_active'];
    protected array $defaults = ['is_active' => true];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean'],
        ['key' => 'notes', 'label' => 'Notes'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
