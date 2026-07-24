<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function trialBalance(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $report = $service->trialBalance($data['date_from'] ?? null, $data['date_to'] ?? null);

        if (($data['format'] ?? null) === 'csv') {
            return $this->trialBalanceCsv($report);
        }

        return view('backend.accounting.reports.trial_balance', [
            'title' => 'Trial Balance',
            'filters' => $data,
            'report' => $report,
        ]);
    }

    public function profitLoss(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $report = $service->profitAndLoss($data['date_from'] ?? null, $data['date_to'] ?? null);

        if (($data['format'] ?? null) === 'csv') {
            return $this->profitLossCsv($report);
        }

        return view('backend.accounting.reports.profit_loss', [
            'title' => 'Profit & Loss',
            'filters' => $data,
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $report = $service->balanceSheet($data['as_of'] ?? null);

        if (($data['format'] ?? null) === 'csv') {
            return $this->balanceSheetCsv($report);
        }

        return view('backend.accounting.reports.balance_sheet', [
            'title' => 'Balance Sheet',
            'filters' => $data,
            'report' => $report,
        ]);
    }

    public function arAging(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $report = $service->arAging($data['as_of'] ?? null);

        if (($data['format'] ?? null) === 'csv') {
            return $this->agingCsv($report, 'ar-aging.csv', 'Customer');
        }

        return view('backend.accounting.reports.ar_aging', [
            'title' => 'Accounts Receivable Aging',
            'filters' => $data,
            'report' => $report,
        ]);
    }

    public function apAging(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $report = $service->apAging($data['as_of'] ?? null);

        if (($data['format'] ?? null) === 'csv') {
            return $this->agingCsv($report, 'ap-aging.csv', 'Vendor');
        }

        return view('backend.accounting.reports.ap_aging', [
            'title' => 'Accounts Payable Aging',
            'filters' => $data,
            'report' => $report,
        ]);
    }

    private function trialBalanceCsv(array $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Account', 'Type', 'Debit', 'Credit']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['code'], $row['name'], $row['type'],
                    number_format($row['debit'], 2), number_format($row['credit'], 2),
                ]);
            }
            fputcsv($out, ['', 'TOTAL', '', number_format($report['total_debit'], 2), number_format($report['total_credit'], 2)]);
            fclose($out);
        }, 'trial-balance.csv', ['Content-Type' => 'text/csv']);
    }

    private function profitLossCsv(array $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Account', 'Amount']);
            fputcsv($out, ['', '--- INCOME ---', '']);
            foreach ($report['income_rows'] as $row) {
                fputcsv($out, [$row['code'], $row['name'], number_format($row['amount'], 2)]);
            }
            fputcsv($out, ['', 'Total Income', number_format($report['total_income'], 2)]);
            fputcsv($out, ['', '--- EXPENSES ---', '']);
            foreach ($report['expense_rows'] as $row) {
                fputcsv($out, [$row['code'], $row['name'], number_format($row['amount'], 2)]);
            }
            fputcsv($out, ['', 'Total Expenses', number_format($report['total_expense'], 2)]);
            fputcsv($out, ['', 'NET INCOME', number_format($report['net_income'], 2)]);
            fclose($out);
        }, 'profit-loss.csv', ['Content-Type' => 'text/csv']);
    }

    private function balanceSheetCsv(array $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Account', 'Amount']);
            fputcsv($out, ['', '--- ASSETS ---', '']);
            foreach ($report['asset_rows'] as $row) {
                fputcsv($out, [$row['code'], $row['name'], number_format($row['balance'], 2)]);
            }
            fputcsv($out, ['', 'Total Assets', number_format($report['total_assets'], 2)]);
            fputcsv($out, ['', '--- LIABILITIES ---', '']);
            foreach ($report['liability_rows'] as $row) {
                fputcsv($out, [$row['code'], $row['name'], number_format($row['balance'], 2)]);
            }
            fputcsv($out, ['', 'Total Liabilities', number_format($report['total_liabilities'], 2)]);
            fputcsv($out, ['', '--- EQUITY ---', '']);
            foreach ($report['equity_rows'] as $row) {
                fputcsv($out, [$row['code'], $row['name'], number_format($row['balance'], 2)]);
            }
            fputcsv($out, ['', 'Retained Earnings', number_format($report['retained_earnings'], 2)]);
            fputcsv($out, ['', 'Total Equity', number_format($report['total_equity'], 2)]);
            fputcsv($out, ['', 'Total Liabilities & Equity', number_format($report['total_liabilities_and_equity'], 2)]);
            fclose($out);
        }, 'balance-sheet.csv', ['Content-Type' => 'text/csv']);
    }

    private function agingCsv(array $report, string $filename, string $partyLabel): StreamedResponse
    {
        return response()->streamDownload(function () use ($report, $partyLabel) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice #', $partyLabel, 'Invoice Date', 'Due Date', 'Total', 'Balance', 'Days Overdue', 'Bucket']);
            $bucketLabels = ['current' => '0-30', '31_60' => '31-60', '61_90' => '61-90', '91_120' => '91-120', 'over_120' => '120+'];
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['invoice_no'],
                    $row[$partyLabel === 'Customer' ? 'customer' : 'vendor'],
                    $row['invoice_date'], $row['due_date'],
                    number_format($row['total'], 2), number_format($row['balance'], 2),
                    $row['days_overdue'],
                    $bucketLabels[$row['bucket']] ?? $row['bucket'],
                ]);
            }
            fputcsv($out, ['', '', '', '', '', number_format($report['total'], 2), '', '']);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
