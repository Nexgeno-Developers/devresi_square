<?php

namespace App\Http\Controllers\Backend\Accounting\Masters;

use App\Http\Controllers\Backend\Accounting\BaseCrudController;
use App\Models\SysInvoiceHeader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SysInvoiceHeaderController extends BaseCrudController
{
    protected string $modelClass = SysInvoiceHeader::class;
    protected string $viewPath = 'backend.accounting.masters.invoice_headers';
    protected string $routeName = 'backend.accounting.masters.invoice_headers';
    protected string $title = 'Invoice Headers';
    protected array $defaults = ['status' => 'active'];
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'header_name', 'label' => 'Header Name'],
        ['key' => 'unique_reference_number', 'label' => 'Unique Reference Number'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'header_description', 'label' => 'Header Description'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'header_name', 'label' => 'Header Name', 'type' => 'text', 'required' => true],
            ['name' => 'unique_reference_number', 'label' => 'Unique Reference Number', 'type' => 'text'],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
            ],
            ['name' => 'header_description', 'label' => 'Header Description', 'type' => 'textarea'],
        ];
    }

    protected function rules(?int $id = null): array
    {
        $uniqueReference = Rule::unique('sys_invoice_headers', 'unique_reference_number');

        if ($id) {
            $uniqueReference = $uniqueReference->ignore($id);
        }

        return [
            'header_name' => ['required', 'string', 'max:255'],
            'header_description' => ['nullable', 'string'],
            'unique_reference_number' => ['nullable', 'string', 'max:100', $uniqueReference],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function onStore(Request $request, array &$data): void
    {
        if (empty($data['unique_reference_number'])) {
            $data['unique_reference_number'] = $this->generateUniqueReferenceNumber();
        }
    }

    protected function onUpdate(Request $request, array &$data, \Illuminate\Database\Eloquent\Model $item): void
    {
        if (empty($data['unique_reference_number'])) {
            $data['unique_reference_number'] = $this->generateUniqueReferenceNumber();
        }
    }

    private function generateUniqueReferenceNumber(): string
    {
        do {
            $reference = 'INVH-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        } while (SysInvoiceHeader::where('unique_reference_number', $reference)->exists());

        return $reference;
    }

    public function ajaxSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 20);

        $query = SysInvoiceHeader::query()->where('status', 'active');

        if ($q === '') {
            $query->orderByDesc('id')->limit(3);
        } else {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('header_name', 'like', "%{$q}%")
                    ->orWhere('unique_reference_number', 'like', "%{$q}%")
                    ->orWhere('header_description', 'like', "%{$q}%");
            })->orderBy('header_name')->limit($limit);
        }

        $results = $query->get()
            ->map(fn (SysInvoiceHeader $header) => $this->mapHeaderForSelect($header))
            ->values();

        return response()->json(['results' => $results]);
    }

    public function ajaxGet(SysInvoiceHeader $invoiceHeader): JsonResponse
    {
        return response()->json($this->mapHeaderForSelect($invoiceHeader, true));
    }

    private function mapHeaderForSelect(SysInvoiceHeader $header, bool $includeMeta = false): array
    {
        $payload = [
            'id' => $header->id,
            'text' => $header->header_name . ' (' . $header->unique_reference_number . ')',
            'header_name' => $header->header_name,
            'unique_reference_number' => $header->unique_reference_number,
            'status' => $header->status,
        ];

        if ($includeMeta) {
            $payload['header_description'] = $header->header_description;
        }

        return $payload;
    }
}
