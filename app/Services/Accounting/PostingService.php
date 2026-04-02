<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlAccountBalance;
use App\Models\GlJournal;
use App\Models\GlJournalLine;
use App\Models\SysBankAccount;
use App\Models\SysPayment;
use App\Models\SysReceipt;
use App\Models\SysSaleInvoice;
use App\Models\SysPurchaseInvoice;
use App\Models\GlPeriodClose;
use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PostingService
{
    /**
     * Post sales invoice issuance to GL (AR vs Revenue) using configurable accounts.
     */
    public function postSaleInvoice(SysSaleInvoice $invoice): ?GlJournal
    {
        $arId = $this->requireAccountIdFromSetting('default_ar_account_id');
        $revId = $this->requireAccountIdFromSetting('default_revenue_account_id');

        $journal = $this->createJournal('sale_invoice_issue', $invoice->id, 'Issue invoice ' . ($invoice->invoice_no ?? $invoice->id), $invoice->invoice_date ?? now());
        $companyId = optional($invoice->user)->company_id;
        $userId = $invoice->user_id;

        $total = (float) ($invoice->total_amount ?? 0);
        $penaltyAppliedAt = $invoice->penalty_applied_at;
        $penaltyAmountApplied = (float) ($invoice->penalty_amount_applied ?? 0);
        $hasPenalty = !empty($penaltyAppliedAt) && $penaltyAmountApplied > 0.0001;

        $creditsByAccount = [];
        if ($hasPenalty) {
            $penaltyIncomeAccId = (int) ($invoice->penalty_gl_account_id ?? $revId);
            $baseRevenueAmount = max(0.0, $total - $penaltyAmountApplied);
            if ($baseRevenueAmount > 0.0001) {
                $creditsByAccount[$revId] = $baseRevenueAmount;
            }
            if ($penaltyAmountApplied > 0.0001) {
                $creditsByAccount[$penaltyIncomeAccId] = ($creditsByAccount[$penaltyIncomeAccId] ?? 0) + $penaltyAmountApplied;
            }
        } else {
            $creditsByAccount[$revId] = $total;
        }

        $tuples = [];
        $tuples[] = [$arId, $total, 0];
        foreach ($creditsByAccount as $accId => $creditAmount) {
            if (abs((float)$creditAmount) <= 0.0001) {
                continue;
            }
            $tuples[] = [(int) $accId, 0, (float) $creditAmount];
        }

        $this->lines($journal, $tuples, $companyId, $userId);

        return $journal;
    }

    /**
     * Post purchase invoice issuance to GL (Expense vs AP) using configurable accounts.
     */
    public function postPurchaseInvoice(SysPurchaseInvoice $invoice): ?GlJournal
    {
        $apId = $this->requireAccountIdFromSetting('default_ap_account_id');
        $expId = $this->requireAccountIdFromSetting('default_expense_account_id');

        $journal = $this->createJournal('purchase_invoice_issue', $invoice->id, 'Post purchase invoice ' . ($invoice->invoice_no ?? $invoice->id), $invoice->invoice_date ?? now());
        $companyId = optional($invoice->user)->company_id;
        $userId = $invoice->user_id;

        $this->lines($journal, [
            [$expId, $invoice->total_amount ?? 0, 0],
            [$apId, 0, $invoice->total_amount ?? 0],
        ], $companyId, $userId);

        return $journal;
    }

    public function postAdvanceReceipt(SysReceipt $receipt): ?GlJournal
    {
        $bankAccount = SysBankAccount::find($receipt->sys_bank_account_id ?? $receipt->bank_account_id);
        $cashAccId = $this->bankGlIdOrFail($bankAccount);
        $advancesId = $this->requireAccountId('2200');

        $journal = $this->createJournal('receipt', $receipt->id, 'Advance receipt ' . $receipt->receipt_no, $receipt->receipt_date);
        $this->lines($journal, [
            [$cashAccId, $receipt->amount, 0],
            [$advancesId, 0, $receipt->amount],
        ], $receipt->company_id ?? null, $receipt->user_id ?? null);

        $receipt->update(['gl_journal_id' => $journal->id]);
        return $journal;
    }

    public function postApplyCredit(SysSaleInvoice $invoice, SysReceipt $receipt, float $amount): ?GlJournal
    {
        $advancesId = $this->requireAccountId('2200');
        $arId = $this->requireAccountIdFromSetting('default_ar_account_id');

        $journal = $this->createJournal('sale_invoice_apply_credit', $invoice->id, 'Apply credit from ' . $receipt->receipt_no, now());
        $companyId = optional($invoice->user)->company_id;
        $this->lines($journal, [
            [$advancesId, $amount, 0],
            [$arId, 0, $amount],
        ], $companyId, $invoice->user_id);

        return $journal;
    }

    public function postStripePayment(SysSaleInvoice $invoice, SysPayment $payment, ?string $memo = null): ?GlJournal
    {
        $bankAccount = SysBankAccount::find($payment->sys_bank_account_id ?? $payment->bank_account_id);
        $cashAccId = $this->bankGlIdOrFail($bankAccount);
        $arId = $this->requireAccountIdFromSetting('default_ar_account_id');
        $memo = $memo ?? ('Stripe payment ' . $invoice->invoice_no);
        $journal = $this->createJournal('sale_invoice_payment', $invoice->id, $memo, $payment->payment_date ?? now());
        $companyId = optional($invoice->user)->company_id;
        $this->lines($journal, [
            [$cashAccId, $payment->amount, 0],
            [$arId, 0, $payment->amount],
        ], $companyId, $payment->user_id);
        $payment->update(['gl_journal_id' => $journal->id]);
        return $journal;
    }

    /**
     * Post vendor/purchase payment to GL (Dr AP, Cr Bank).
     */
    public function postPurchasePayment(SysPurchaseInvoice $invoice, SysPayment $payment): ?GlJournal
    {
        $apId = $this->requireAccountIdFromSetting('default_ap_account_id');
        $bankAccount = SysBankAccount::find($payment->sys_bank_account_id ?? $payment->bank_account_id);
        $cashAccId = $this->bankGlIdOrFail($bankAccount);

        $memo = 'Payment for purchase invoice ' . ($invoice->invoice_no ?? $invoice->id);
        $journal = $this->createJournal('purchase_invoice_payment', $invoice->id, $memo, $payment->payment_date ?? now());
        $companyId = optional($invoice->user)->company_id;

        $this->lines($journal, [
            [$apId, $payment->amount, 0],
            [$cashAccId, 0, $payment->amount],
        ], $companyId, $payment->user_id ?? $invoice->user_id);

        $payment->update(['gl_journal_id' => $journal->id]);
        return $journal;
    }

    public function postUndoCredit(SysSaleInvoice $invoice, SysReceipt $receipt, float $amount): ?GlJournal
    {
        $advancesId = $this->requireAccountId('2200');
        $arId = $this->requireAccountIdFromSetting('default_ar_account_id');

        $journal = $this->createJournal('sale_invoice_undo_credit', $invoice->id, 'Undo credit from ' . $receipt->receipt_no, now());
        $this->lines($journal, [
            [$advancesId, 0, $amount],
            [$arId, $amount, 0],
        ], optional(Auth::user())->company_id ?? null, $invoice->user_id);

        return $journal;
    }

    public function updateSaleIssueJournal(SysSaleInvoice $invoice, GlJournal $journal): GlJournal
    {
        $arId = $this->requireAccountIdFromSetting('default_ar_account_id');
        $revId = $this->requireAccountIdFromSetting('default_revenue_account_id');
        $companyId = optional($invoice->user)->company_id;

        $total = (float) ($invoice->total_amount ?? 0);
        $penaltyAppliedAt = $invoice->penalty_applied_at;
        $penaltyAmountApplied = (float) ($invoice->penalty_amount_applied ?? 0);
        $hasPenalty = !empty($penaltyAppliedAt) && $penaltyAmountApplied > 0.0001;

        $creditsByAccount = [];
        if ($hasPenalty) {
            $penaltyIncomeAccId = (int) ($invoice->penalty_gl_account_id ?? $revId);
            $baseRevenueAmount = max(0.0, $total - $penaltyAmountApplied);
            if ($baseRevenueAmount > 0.0001) {
                $creditsByAccount[$revId] = $baseRevenueAmount;
            }
            if ($penaltyAmountApplied > 0.0001) {
                $creditsByAccount[$penaltyIncomeAccId] = ($creditsByAccount[$penaltyIncomeAccId] ?? 0) + $penaltyAmountApplied;
            }
        } else {
            $creditsByAccount[$revId] = $total;
        }

        $desired = [
            ['account_id' => $arId, 'debit' => $total, 'credit' => 0],
        ];
        foreach ($creditsByAccount as $accId => $creditAmount) {
            if (abs((float)$creditAmount) <= 0.0001) {
                continue;
            }
            $desired[] = ['account_id' => (int) $accId, 'debit' => 0, 'credit' => (float) $creditAmount];
        }

        $this->syncJournalLines($journal, $desired, $companyId, $invoice->user_id);
        return $journal->fresh('lines');
    }

    public function updatePurchaseIssueJournal(SysPurchaseInvoice $invoice, GlJournal $journal): GlJournal
    {
        $apId = $this->requireAccountIdFromSetting('default_ap_account_id');
        $expId = $this->requireAccountIdFromSetting('default_expense_account_id');
        $companyId = optional($invoice->user)->company_id;
        $desired = [
            ['account_id' => $expId, 'debit' => $invoice->total_amount ?? 0, 'credit' => 0],
            ['account_id' => $apId, 'debit' => 0, 'credit' => $invoice->total_amount ?? 0],
        ];
        $this->syncJournalLines($journal, $desired, $companyId, $invoice->user_id);
        return $journal->fresh('lines');
    }

    public function deleteJournalAndBalances(GlJournal $journal): void
    {
        $date = $journal->date;
        foreach ($journal->lines as $line) {
            $this->applyBalanceDelta($line->gl_account_id, $date, -$line->debit, -$line->credit);
            $line->delete();
        }
        $journal->delete();
    }

    private function syncJournalLines(GlJournal $journal, array $desiredLines, ?int $companyId, ?int $userId): void
    {
        $date = $journal->date;
        $existing = $journal->lines()->get();
        $desiredMap = collect($desiredLines)->keyBy('account_id');
        $handled = [];

        foreach ($existing as $line) {
            $target = $desiredMap->get($line->gl_account_id);
            if ($target) {
                $deltaDebit = $target['debit'] - (float)$line->debit;
                $deltaCredit = $target['credit'] - (float)$line->credit;
                if (abs($deltaDebit) > 0.0001 || abs($deltaCredit) > 0.0001) {
                    $this->applyBalanceDelta($line->gl_account_id, $date, $deltaDebit, $deltaCredit);
                    $line->update([
                        'debit' => $target['debit'],
                        'credit' => $target['credit'],
                        'user_id' => $userId,
                        'company_id' => $companyId,
                    ]);
                }
                $handled[] = $line->gl_account_id;
            } else {
                $this->applyBalanceDelta($line->gl_account_id, $date, -$line->debit, -$line->credit);
                $line->delete();
            }
        }

        foreach ($desiredMap as $accId => $target) {
            if (in_array($accId, $handled, true)) {
                continue;
            }
            GlJournalLine::create([
                'gl_journal_id' => $journal->id,
                'gl_account_id' => $accId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'debit' => $target['debit'],
                'credit' => $target['credit'],
            ]);
            $this->applyBalanceDelta($accId, $date, $target['debit'], $target['credit']);
        }
    }

    private function applyBalanceDelta(int $accountId, $date, float $deltaDebit, float $deltaCredit): void
    {
        $period = Carbon::parse($date)->format('Y-m');
        $bal = GlAccountBalance::firstOrNew(['gl_account_id' => $accountId, 'period' => $period]);
        $bal->debit = ($bal->debit ?? 0) + $deltaDebit;
        $bal->credit = ($bal->credit ?? 0) + $deltaCredit;
        $bal->save();
    }

    private function bankGlIdOrFail(?SysBankAccount $bank): int
    {
        if ($bank && $bank->gl_account_id) {
            return $bank->gl_account_id;
        }
        return $this->requireAccountId('1100');
    }

    private function accountIdByCode(string $code): ?int
    {
        return GlAccount::where('code', $code)->value('id');
    }

    private function requireAccountIdFromSetting(string $key): int
    {
        $id = BusinessSetting::where('type', $key)->value('value');
        if (!$id) {
            throw new \RuntimeException("Missing setting {$key}. Please set GL account ID before posting.");
        }
        return (int) $id;
    }

    private function requireAccountId(string $code): int
    {
        $id = $this->accountIdByCode($code);
        if (!$id) {
            throw new \RuntimeException("Missing GL account code {$code}. Please set it up before posting.");
        }
        return $id;
    }

    public function reverseJournal(int $originalJournalId, string $memoPrefix = 'Reversal'): ?GlJournal
    {
        $original = GlJournal::with('lines')->find($originalJournalId);
        if (!$original) {
            return null;
        }

        // do not reverse a reversal journal and prevent double reversal
        if ($original->reversal_of_id) {
            return null;
        }
        if (GlJournal::where('reversal_of_id', $original->id)->exists()) {
            return null;
        }

        $memo = $memoPrefix . ' of JNL-' . $original->id;
        $journal = $this->createJournal(
            $original->source_type,
            $original->source_id,
            $memo,
            now(),
            ['reversal_of_id' => $original->id]
        );

        foreach ($original->lines as $line) {
            GlJournalLine::create([
                'gl_journal_id' => $journal->id,
                'gl_account_id' => $line->gl_account_id,
                'company_id' => $line->company_id,
                'user_id' => $line->user_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
            ]);
            $this->updateBalance($line->gl_account_id, $journal->date, $line->credit, $line->debit);
        }

        return $journal;
    }

    private function createJournal(string $sourceType, ?int $sourceId, string $memo, $date, array $extra = []): GlJournal
    {
        $dateStr = Carbon::parse($date)->toDateString();
        $this->assertPeriodOpen($dateStr);

        $attrs = [
            'date' => $dateStr,
            'memo' => $memo,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ];

        return GlJournal::create(array_merge($attrs, $extra));
    }

    private function assertPeriodOpen(string $date): void
    {
        $period = Carbon::parse($date)->format('Y-m');

        $closed = GlPeriodClose::where('period', $period)
            ->where('is_closed', true)
            ->exists();

        if ($closed) {
            throw new \RuntimeException("Period {$period} is closed. Cannot post journal entries to a closed period.");
        }
    }

    private function lines(GlJournal $journal, array $tuples, $companyId, $userId): void
    {
        foreach ($tuples as [$accId, $debit, $credit]) {
            GlJournalLine::create([
                'gl_journal_id' => $journal->id,
                'gl_account_id' => $accId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'debit' => $debit,
                'credit' => $credit,
            ]);
            $this->updateBalance($accId, $journal->date, $debit, $credit);
        }
    }

    private function updateBalance(int $accountId, $date, float $debit, float $credit): void
    {
        $period = Carbon::parse($date)->format('Y-m');
        $bal = GlAccountBalance::firstOrNew(['gl_account_id' => $accountId, 'period' => $period]);
        $bal->debit = ($bal->debit ?? 0) + $debit;
        $bal->credit = ($bal->credit ?? 0) + $credit;
        $bal->save();
    }
}
