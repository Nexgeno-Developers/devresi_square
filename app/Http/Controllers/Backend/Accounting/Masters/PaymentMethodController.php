<?php

namespace App\Http\Controllers\Backend\Accounting\Masters;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;

class PaymentMethodController extends BaseCrudController
{
    protected string $modelClass = PaymentMethod::class;
    protected string $viewPath = 'backend.accounting.masters.payment_methods';
    protected string $routeName = 'backend.accounting.masters.payment_methods';
    protected string $title = 'Payment Methods';
    protected array $booleanFields = ['is_active'];
    protected array $defaults = ['is_active' => true];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        $uniqueCode = Rule::unique('payment_methods', 'code');
        if ($id) {
            $uniqueCode = $uniqueCode->ignore($id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', $uniqueCode],
        ];
    }
}
