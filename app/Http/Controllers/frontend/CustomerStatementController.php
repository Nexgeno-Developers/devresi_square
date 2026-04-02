<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Accounting\StatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerStatementController extends Controller
{
    public function show(Request $request, StatementService $service)
    {
        $user = Auth::user();

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', 'in:csv,html'],
        ]);

        $statement = $service->customerStatement(
            (int) $user->id,
            isset($data['company_id']) ? (int) $data['company_id'] : ($user->company_id ?? null),
            $data['date_from'] ?? null,
            $data['date_to'] ?? null
        );

        if (($data['format'] ?? null) === 'csv') {
            return $this->streamCsv($statement, 'my-statement.csv');
        }

        return view('frontend.customer.statement', [
            'title' => 'My Statement',
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'filters' => $data + [
                'date_from' => $statement['from'],
                'date_to' => $statement['to'],
            ],
            'statement' => $statement,
        ]);
    }

    private function streamCsv(array $statement, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($statement) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Memo', 'Source', 'Account', 'Debit', 'Credit', 'Delta', 'Running']);
            fputcsv($out, ['', 'Opening Balance', '', '', '', '', '', number_format($statement['opening'], 2)]);
            foreach ($statement['lines'] as $line) {
                fputcsv($out, [
                    $line['date'],
                    $line['memo'],
                    trim(($line['source_type'] ?? '') . ' ' . ($line['source_id'] ?? '')),
                    $line['account_code'] . ' ' . $line['account_name'],
                    number_format($line['debit'], 2),
                    number_format($line['credit'], 2),
                    number_format($line['delta'], 2),
                    number_format($line['running'], 2),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
