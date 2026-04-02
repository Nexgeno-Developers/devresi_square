<?php

namespace App\Http\Controllers\Backend\Accounting\Purchase;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysAdjustmentNote;
use App\Models\User;
use Illuminate\Validation\Rule;

class DebitNoteController extends BaseCrudController
{
    protected string $modelClass = SysAdjustmentNote::class;
    protected string $viewPath = 'backend.accounting.purchase.debit_notes';
    protected string $routeName = 'backend.accounting.purchase.debit_notes';
    protected string $title = 'Debit Notes';
    protected array $scope = ['note_type' => 'debit'];
    protected array $fixedValues = ['note_type' => 'debit'];
    protected array $booleanFields = ['is_refunded'];
    protected array $defaults = ['reference_type' => 'purchase_invoice', 'note_date' => null];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'note_no', 'label' => 'Note No'],
        ['key' => 'user_id', 'label' => 'User'],
        ['key' => 'reference_type', 'label' => 'Reference Type'],
        ['key' => 'reference_id', 'label' => 'Reference ID'],
        ['key' => 'total_amount', 'label' => 'Total', 'type' => 'money'],
        ['key' => 'balance_amount', 'label' => 'Balance', 'type' => 'money'],
        ['key' => 'is_refunded', 'label' => 'Refunded', 'type' => 'boolean'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'user_id', 'label' => 'User', 'type' => 'select', 'required' => true],
            ['name' => 'note_no', 'label' => 'Note No', 'type' => 'text', 'required' => true],
            ['name' => 'note_date', 'label' => 'Note Date', 'type' => 'date', 'required' => true],
            ['name' => 'adjustment_reason', 'label' => 'Reason', 'type' => 'select', 'options' => ['return' => 'Return', 'refund' => 'Refund', 'writeoff' => 'Writeoff']],
            ['name' => 'reference_type', 'label' => 'Reference Type', 'type' => 'select', 'options' => ['sale_invoice' => 'Sale Invoice', 'purchase_invoice' => 'Purchase Invoice'], 'required' => true],
            ['name' => 'reference_id', 'label' => 'Reference ID', 'type' => 'number', 'min' => '1', 'required' => true],
            ['name' => 'total_amount', 'label' => 'Total Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'balance_amount', 'label' => 'Balance Amount', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'is_refunded', 'label' => 'Refunded', 'type' => 'checkbox'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    protected function options(): array
    {
        return [
            'user_id' => User::query()->orderBy('name')->pluck('name', 'id')->toArray(),
        ];
    }

    protected function rules(?int $id = null): array
    {
        $uniqueNoteNo = Rule::unique('sys_adjustment_notes', 'note_no');
        if ($id) {
            $uniqueNoteNo = $uniqueNoteNo->ignore($id);
        }

        return [
            'user_id' => ['required', 'exists:users,id'],
            'note_no' => ['required', 'string', 'max:50', $uniqueNoteNo],
            'note_date' => ['required', 'date'],
            'adjustment_reason' => ['nullable', Rule::in(['return', 'refund', 'writeoff'])],
            'reference_type' => ['required', Rule::in(['sale_invoice', 'purchase_invoice'])],
            'reference_id' => ['required', 'integer', 'min:1'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'balance_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
