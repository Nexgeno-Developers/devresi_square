<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Models\GlJournalLine;
use App\Models\SysBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'sys_bank_account_id' => ['nullable', 'exists:sys_bank_accounts,id'],
            'statement_date' => ['nullable', 'date'],
            'statement_balance' => ['nullable', 'numeric'],
        ]);

        $bankAccounts = SysBankAccount::orderBy('account_name')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->id => ($b->account_name ?? $b->bank_name ?? 'Bank #' . $b->id)]);

        $glLines = collect();
        $glBalance = 0;
        $reconciliation = null;

        if (!empty($data['sys_bank_account_id'])) {
            $bank = SysBankAccount::findOrFail($data['sys_bank_account_id']);
            $glAccountId = $bank->gl_account_id;

            if ($glAccountId) {
                $query = GlJournalLine::query()
                    ->select([
                        'gl_journal_lines.*',
                        'gl_journals.date',
                        'gl_journals.memo',
                        'gl_journals.source_type',
                    ])
                    ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                    ->where('gl_journal_lines.gl_account_id', $glAccountId)
                    ->orderBy('gl_journals.date')
                    ->orderBy('gl_journal_lines.id');

                if (!empty($data['statement_date'])) {
                    $query->where('gl_journals.date', '<=', $data['statement_date']);
                }

                $glLines = $query->get();
                $glBalance = (float) $glLines->sum('debit') - (float) $glLines->sum('credit');
            }

            $reconciliation = [
                'gl_balance' => $glBalance,
                'statement_balance' => (float) ($data['statement_balance'] ?? 0),
                'difference' => $glBalance - (float) ($data['statement_balance'] ?? 0),
            ];
        }

        return view('backend.accounting.bank_reconciliation.index', [
            'title' => 'Bank Reconciliation',
            'bankAccounts' => $bankAccounts,
            'filters' => $data,
            'glLines' => $glLines,
            'reconciliation' => $reconciliation,
        ]);
    }

    public function reconcile(Request $request)
    {
        $data = $request->validate([
            'sys_bank_account_id' => ['required', 'exists:sys_bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $bank = SysBankAccount::findOrFail($data['sys_bank_account_id']);
        $glAccountId = $bank->gl_account_id;

        $glBalance = 0;
        if ($glAccountId) {
            $glBalance = (float) GlJournalLine::query()
                ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                ->where('gl_journal_lines.gl_account_id', $glAccountId)
                ->where('gl_journals.date', '<=', $data['statement_date'])
                ->select(DB::raw('SUM(debit) - SUM(credit) as balance'))
                ->value('balance');
        }

        BankReconciliation::create([
            'sys_bank_account_id' => $data['sys_bank_account_id'],
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'gl_balance' => $glBalance,
            'difference' => $glBalance - $data['statement_balance'],
            'status' => abs($glBalance - $data['statement_balance']) < 0.01 ? 'reconciled' : 'draft',
            'reconciled_by' => auth()->id(),
            'reconciled_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('backend.accounting.bank_reconciliation.index', [
            'sys_bank_account_id' => $data['sys_bank_account_id'],
        ])->with('success', 'Reconciliation saved successfully.');
    }
}
