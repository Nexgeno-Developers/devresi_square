<?php

namespace App\Services\Accounting;

use App\Models\SysSaleInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Accounting\PostingService;

class SaleInvoicePenaltyService
{
    /**
     * Apply penalty once when eligible.
     *
     * Eligibility:
     * - penalty_enabled
     * - penalty_applied_at IS NULL
     * - due_date exists and due_date + grace_days < today
     * - status is not cancelled
     * - balance_amount > 0
     *
     * Note: this method updates totals/balance/status + penalty_applied_at/amount.
     * It does not sync GL; caller should call `postInvoiceIfNeeded()` (or equivalent).
     */
    public function applyPenaltyIfEligible(SysSaleInvoice $invoice, ?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();
        $today = $now->copy()->startOfDay();

        $applied = false;

        DB::transaction(function () use (&$applied, $invoice, $now, $today) {
            /** @var SysSaleInvoice $locked */
            $locked = SysSaleInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (empty($locked->penalty_enabled)) {
                return;
            }

            if (!empty($locked->penalty_applied_at)) {
                return;
            }

            if (($locked->status ?? null) === 'cancelled') {
                return;
            }

            if (empty($locked->due_date)) {
                return;
            }

            $balance = (float) ($locked->balance_amount ?? 0);
            if ($balance <= 0.0001) {
                return;
            }

            $graceDays = (int) ($locked->penalty_grace_days ?? 0);
            $dueDate = Carbon::parse($locked->due_date)->startOfDay();
            $effectiveDue = $dueDate->copy()->addDays($graceDays);

            if (! $effectiveDue->lt($today)) {
                return; // not overdue yet (grace applied)
            }

            $penalty = $this->calculatePenaltyAmount($locked, $balance);
            if ($penalty <= 0.0001) {
                return;
            }

            $locked->penalty_applied_at = $now;
            $locked->penalty_amount_applied = $penalty;

            $locked->total_amount = (float) ($locked->total_amount ?? 0) + $penalty;
            $locked->balance_amount = (float) ($locked->balance_amount ?? 0) + $penalty;

            $locked->status = $this->recalculateStatus((float) $locked->balance_amount, (float) $locked->total_amount);
            $locked->save();

            $applied = true;
        });

        if ($applied) {
            // If the invoice was already posted earlier, keep the journal in sync
            // with the updated totals (AR + revenue/penalty splits).
            $fresh = SysSaleInvoice::with('user')->find($invoice->id);
            if ($fresh && $fresh->hasActiveJournal()) {
                $journal = $fresh->activeJournal();
                if ($journal) {
                    app(PostingService::class)->updateSaleIssueJournal($fresh, $journal);
                }
            }
        }

        return $applied;
    }

    /**
     * If a penalty has already been applied, keep `status` consistent
     * with the current (possibly re-computed) total/balance.
     *
     * This is intentionally conservative: it only updates `status`.
     */
    public function reconcileAppliedPenaltyStatus(SysSaleInvoice $invoice, ?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();
        $changed = false;

        DB::transaction(function () use (&$changed, $invoice, $now) {
            $locked = SysSaleInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (empty($locked->penalty_applied_at)) {
                return;
            }

            $lockedTotal = (float) ($locked->total_amount ?? 0);
            $lockedBalance = (float) ($locked->balance_amount ?? 0);

            $newStatus = $this->recalculateStatus($lockedBalance, $lockedTotal);
            if (($locked->status ?? null) !== $newStatus) {
                $locked->status = $newStatus;
                $locked->save();
                $changed = true;
            }
        });

        if ($changed) {
            $fresh = SysSaleInvoice::with('user')->find($invoice->id);
            if ($fresh && $fresh->hasActiveJournal()) {
                $journal = $fresh->activeJournal();
                if ($journal) {
                    app(PostingService::class)->updateSaleIssueJournal($fresh, $journal);
                }
            }
        }

        return $changed;
    }

    private function calculatePenaltyAmount(SysSaleInvoice $invoice, float $baseBalance): float
    {
        $type = $invoice->penalty_type;
        $fixedRate = (float) ($invoice->penalty_fixed_rate ?? 0);

        if ($type === 'percentage') {
            $penalty = ($baseBalance * $fixedRate) / 100;
        } elseif ($type === 'flat_rate') {
            $penalty = $fixedRate;
        } else {
            return 0.0;
        }

        // cap
        $cap = $invoice->penalty_max_amount;
        if ($cap !== null) {
            $capVal = (float) $cap;
            if ($capVal > 0.0001) {
                $penalty = min($penalty, $capVal);
            }
        }

        // ensure non-negative & consistent rounding
        $penalty = max(0.0, $penalty);
        return round($penalty, 2);
    }

    private function recalculateStatus(float $balanceAmount, float $totalAmount): string
    {
        // Because penalty increases both total & balance, the relationship between
        // them still represents paid vs partial vs issued.
        if ($balanceAmount <= 0.0001) {
            return 'paid';
        }

        if ($balanceAmount + 0.0001 < $totalAmount) {
            return 'partial';
        }

        return 'issued';
    }
}

