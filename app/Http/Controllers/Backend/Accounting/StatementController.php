<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GlAccount;
use App\Models\User;
use App\Services\Accounting\StatementService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementController extends Controller
{
    public function customer(Request $request, StatementService $service)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', 'in:csv,html'],
        ]);

        $statement = null;
        if (!empty($data['user_id'])) {
            $statement = $service->customerStatement(
                (int) $data['user_id'],
                isset($data['company_id']) ? (int) $data['company_id'] : null,
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
        }

        if (($data['format'] ?? null) === 'csv' && $statement) {
            return $this->streamCsv($statement, 'customer-statement.csv');
        }

        return view('backend.accounting.statements.customer', [
            'title' => 'Customer Statement',
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'filters' => $data + [
                'date_from' => $statement['from'] ?? null,
                'date_to' => $statement['to'] ?? null,
            ],
            'statement' => $statement,
        ]);
    }

    public function account(Request $request, StatementService $service)
    {
        $data = $request->validate([
            'gl_account_id' => ['nullable', 'exists:gl_accounts,id'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', 'in:csv,html'],
        ]);

        $statement = null;
        if (!empty($data['gl_account_id'])) {
            $statement = $service->accountStatement(
                (int) $data['gl_account_id'],
                isset($data['company_id']) ? (int) $data['company_id'] : null,
                $data['date_from'] ?? null,
                $data['date_to'] ?? null
            );
        }

        if (($data['format'] ?? null) === 'csv' && $statement) {
            return $this->streamCsv($statement, 'account-ledger.csv');
        }

        return view('backend.accounting.statements.account', [
            'title' => 'Account Ledger',
            'accounts' => GlAccount::orderBy('code')
                ->get()
                ->mapWithKeys(fn ($acc) => [$acc->id => $acc->code . ' - ' . $acc->name]),
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'filters' => $data + [
                'date_from' => $statement['from'] ?? null,
                'date_to' => $statement['to'] ?? null,
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
