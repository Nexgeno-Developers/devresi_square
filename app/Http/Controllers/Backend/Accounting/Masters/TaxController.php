<?php

namespace App\Http\Controllers\Backend\Accounting\Masters;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysTax;

class TaxController extends BaseCrudController
{
    protected string $modelClass = SysTax::class;
    protected string $viewPath = 'backend.accounting.masters.taxes';
    protected string $routeName = 'backend.accounting.masters.taxes';
    protected string $title = 'Taxes';
    protected array $booleanFields = ['is_active'];
    protected array $defaults = ['is_active' => true];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'rate', 'label' => 'Rate (%)'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'rate', 'label' => 'Rate (%)', 'type' => 'number', 'step' => '0.01', 'min' => '0'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
