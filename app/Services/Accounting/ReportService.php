<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlJournalLine;
use App\Models\SysSaleInvoice;
use App\Models\SysPurchaseInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function trialBalance(?string $from, ?string $to): array
    {
        $query = GlJournalLine::query()
            ->select(
                'gl_journal_lines.gl_account_id',
                DB::raw('SUM(gl_journal_lines.debit) as total_debit'),
                DB::raw('SUM(gl_journal_lines.credit) as total_credit')
            )
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->groupBy('gl_journal_lines.gl_account_id');

        if ($from) {
            $query->where('gl_journals.date', '>=', $from);
        }
        if ($to) {
            $query->where('gl_journals.date', '<=', $to);
        }

        $results = $query->get()->keyBy('gl_account_id');
        $accounts = GlAccount::where('is_active', true)->orderBy('code')->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $data = $results->get($account->id);
            $debit = $data ? (float) $data->total_debit : 0;
            $credit = $data ? (float) $data->total_credit : 0;

            if (abs($debit) < 0.001 && abs($credit) < 0.001) {
                continue;
            }

            $type = strtolower($account->type);
            $net = $debit - $credit;

            if (in_array($type, ['asset', 'expense'])) {
                $balanceDebit = $net > 0 ? $net : 0;
                $balanceCredit = $net < 0 ? abs($net) : 0;
            } else {
                $balanceDebit = $net > 0 ? $net : 0;
                $balanceCredit = $net < 0 ? abs($net) : 0;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $debit,
                'credit' => $credit,
                'balance_debit' => $balanceDebit,
                'balance_credit' => $balanceCredit,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => round($totalDebit - $totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    public function profitAndLoss(?string $from, ?string $to): array
    {
        $from = $from ?: Carbon::now()->startOfYear()->toDateString();

        $query = GlJournalLine::query()
            ->select(
                'gl_journal_lines.gl_account_id',
                DB::raw('SUM(gl_journal_lines.debit) as total_debit'),
                DB::raw('SUM(gl_journal_lines.credit) as total_credit')
            )
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->whereRaw('LOWER(gl_accounts.type) IN (?, ?)', ['income', 'expense'])
            ->where('gl_journals.date', '>=', $from)
            ->groupBy('gl_journal_lines.gl_account_id');

        if ($to) {
            $query->where('gl_journals.date', '<=', $to);
        }

        $results = $query->get()->keyBy('gl_account_id');

        $accounts = GlAccount::where('is_active', true)
            ->whereRaw('LOWER(type) IN (?, ?)', ['income', 'expense'])
            ->orderBy('code')
            ->get();

        $incomeRows = [];
        $expenseRows = [];
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $data = $results->get($account->id);
            $debit = $data ? (float) $data->total_debit : 0;
            $credit = $data ? (float) $data->total_credit : 0;
            $type = strtolower($account->type);

            if ($type === 'income') {
                $amount = $credit - $debit;
                if (abs($amount) < 0.001) continue;
                $totalIncome += $amount;
                $incomeRows[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            } else {
                $amount = $debit - $credit;
                if (abs($amount) < 0.001) continue;
                $totalExpense += $amount;
                $expenseRows[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        return [
            'income_rows' => $incomeRows,
            'expense_rows' => $expenseRows,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_income' => $totalIncome - $totalExpense,
        ];
    }

    public function balanceSheet(?string $asOf): array
    {
        $asOf = $asOf ?: Carbon::now()->toDateString();

        $query = GlJournalLine::query()
            ->select(
                'gl_journal_lines.gl_account_id',
                DB::raw('SUM(gl_journal_lines.debit) as total_debit'),
                DB::raw('SUM(gl_journal_lines.credit) as total_credit')
            )
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->where('gl_journals.date', '<=', $asOf)
            ->groupBy('gl_journal_lines.gl_account_id');

        $results = $query->get()->keyBy('gl_account_id');
        $accounts = GlAccount::where('is_active', true)->orderBy('code')->get();

        $assetRows = [];
        $liabilityRows = [];
        $equityRows = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $data = $results->get($account->id);
            $debit = $data ? (float) $data->total_debit : 0;
            $credit = $data ? (float) $data->total_credit : 0;
            $type = strtolower($account->type);

            switch ($type) {
                case 'asset':
                    $balance = $debit - $credit;
                    $totalAssets += $balance;
                    if (abs($balance) >= 0.001) {
                        $assetRows[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                    }
                    break;
                case 'liability':
                    $balance = $credit - $debit;
                    $totalLiabilities += $balance;
                    if (abs($balance) >= 0.001) {
                        $liabilityRows[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                    }
                    break;
                case 'equity':
                    $balance = $credit - $debit;
                    $totalEquity += $balance;
                    if (abs($balance) >= 0.001) {
                        $equityRows[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                    }
                    break;
                case 'income':
                    $totalIncome += ($credit - $debit);
                    break;
                case 'expense':
                    $totalExpense += ($debit - $credit);
                    break;
            }
        }

        $retainedEarnings = $totalIncome - $totalExpense;
        $totalEquity += $retainedEarnings;

        return [
            'as_of' => $asOf,
            'asset_rows' => $assetRows,
            'liability_rows' => $liabilityRows,
            'equity_rows' => $equityRows,
            'retained_earnings' => $retainedEarnings,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    public function arAging(?string $asOf = null): array
    {
        $asOf = $asOf ? Carbon::parse($asOf) : Carbon::now();

        $invoices = SysSaleInvoice::with('user')
            ->where('balance_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get();

        $buckets = ['current' => 0, '31_60' => 0, '61_90' => 0, '91_120' => 0, 'over_120' => 0];
        $rows = [];

        foreach ($invoices as $inv) {
            $dueDate = Carbon::parse($inv->due_date ?? $inv->invoice_date);
            $daysOverdue = max(0, $dueDate->diffInDays($asOf, false));
            $balance = (float) $inv->balance_amount;

            $bucket = match (true) {
                $daysOverdue <= 30 => 'current',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                $daysOverdue <= 120 => '91_120',
                default => 'over_120',
            };

            $buckets[$bucket] += $balance;

            $rows[] = [
                'invoice_no' => $inv->invoice_no,
                'customer' => optional($inv->user)->name ?? 'N/A',
                'user_id' => $inv->user_id,
                'invoice_date' => $inv->invoice_date,
                'due_date' => $inv->due_date ?? $inv->invoice_date,
                'total' => (float) $inv->total_amount,
                'balance' => $balance,
                'days_overdue' => $daysOverdue,
                'bucket' => $bucket,
            ];
        }

        usort($rows, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows,
            'buckets' => $buckets,
            'total' => array_sum($buckets),
        ];
    }

    public function apAging(?string $asOf = null): array
    {
        $asOf = $asOf ? Carbon::parse($asOf) : Carbon::now();

        $invoices = SysPurchaseInvoice::with('user')
            ->where('balance_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get();

        $buckets = ['current' => 0, '31_60' => 0, '61_90' => 0, '91_120' => 0, 'over_120' => 0];
        $rows = [];

        foreach ($invoices as $inv) {
            $dueDate = Carbon::parse($inv->due_date ?? $inv->invoice_date);
            $daysOverdue = max(0, $dueDate->diffInDays($asOf, false));
            $balance = (float) $inv->balance_amount;

            $bucket = match (true) {
                $daysOverdue <= 30 => 'current',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                $daysOverdue <= 120 => '91_120',
                default => 'over_120',
            };

            $buckets[$bucket] += $balance;

            $rows[] = [
                'invoice_no' => $inv->invoice_no,
                'vendor' => optional($inv->user)->name ?? 'N/A',
                'user_id' => $inv->user_id,
                'invoice_date' => $inv->invoice_date,
                'due_date' => $inv->due_date ?? $inv->invoice_date,
                'total' => (float) $inv->total_amount,
                'balance' => $balance,
                'days_overdue' => $daysOverdue,
                'bucket' => $bucket,
            ];
        }

        usort($rows, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows,
            'buckets' => $buckets,
            'total' => array_sum($buckets),
        ];
    }
}
