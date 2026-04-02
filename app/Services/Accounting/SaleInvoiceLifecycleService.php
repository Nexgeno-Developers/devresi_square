<?php

namespace App\Services\Accounting;

use App\Models\BusinessSetting;
use App\Models\GlJournal;
use App\Models\SysSaleInvoice;
use App\Models\SysSaleInvoiceItem;
use Illuminate\Support\Facades\DB;

class SaleInvoiceLifecycleService
{
    /**
     * Calculate and generate the next invoice number string.
     */
    public function nextInvoiceNo(): string
    {
        $prefix = BusinessSetting::where('type', 'sale_invoice_prefix')->value('value') ?? 'INV';
        $next = (SysSaleInvoice::max('id') ?? 0) + 1;
        return $prefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Persist invoice + items (create or update) and recalculate totals.
     *
     * Expects `$data['items']` to be present using the same shape as the controller request.
     */
    public function persistItems(array &$data, ?SysSaleInvoice $invoice): SysSaleInvoice
    {
        $items = $data['items'];
        unset($data['items']);

        $persisted = null;
        $existingPenaltyAppliedAmount = 0.0;
        if ($invoice && !empty($invoice->penalty_applied_at)) {
            $existingPenaltyAppliedAmount = (float) ($invoice->penalty_amount_applied ?? 0);
        }

        DB::transaction(function () use (&$data, $items, $invoice, &$persisted, $existingPenaltyAppliedAmount) {
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $linePayloads = [];

            foreach ($items as $row) {
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $discount = (float) ($row['discount'] ?? 0);
                $taxRate = (float) ($row['tax_rate'] ?? 0);

                $lineBase = max(0, ($qty * $rate) - $discount);
                $taxAmount = $taxRate > 0 ? ($lineBase * $taxRate / 100) : 0;
                $lineTotal = $lineBase + $taxAmount;

                $subtotal += $lineBase;
                $taxTotal += $taxAmount;

                $linePayloads[] = [
                    'item_name' => $row['item_name'],
                    'description' => $row['description'] ?? null,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'discount' => $discount,
                    'tax_id' => $row['tax_id'] ?? null,
                    'tax_rate' => $taxRate ?: null,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            $itemsTotal = $subtotal + $taxTotal;
            $totalWithPenalty = $itemsTotal + $existingPenaltyAppliedAmount;
            $data['total_amount'] = $totalWithPenalty;

            // UI `balance_amount` is calculated from items only; if a penalty was already applied,
            // we must keep it reflected in totals when items are edited.
            $balanceInput = array_key_exists('balance_amount', $data) ? (float) ($data['balance_amount'] ?? $itemsTotal) : $itemsTotal;
            $data['balance_amount'] = $balanceInput + $existingPenaltyAppliedAmount;

            if ($invoice) {
                $invoice->update($data);
                $invoice->items()->delete();
                $invoiceId = $invoice->id;
                $persisted = $invoice;
            } else {
                $invoice = SysSaleInvoice::create($data);
                $invoiceId = $invoice->id;
                $persisted = $invoice;
            }

            foreach ($linePayloads as $payload) {
                $payload['sale_invoice_id'] = $invoiceId;
                SysSaleInvoiceItem::create($payload);
            }
        });

        if (! $persisted) {
            throw new \RuntimeException('Sale invoice persistence failed.');
        }

        return $persisted;
    }

    /**
     * Post invoice to GL when its status requires posting.
     */
    public function postInvoiceIfNeeded(SysSaleInvoice $invoice): void
    {
        $status = $invoice->status ?? 'draft';
        $needsPosting = in_array($status, ['issued', 'paid', 'partial', 'posted'], true);

        $issueJournals = GlJournal::issueFor('sale', $invoice->id)->get();
        if ($issueJournals->count() > 1) {
            $keeper = $issueJournals->first();
            $issueJournals->slice(1)->each(function (GlJournal $jnl) {
                app(PostingService::class)->deleteJournalAndBalances($jnl);
            });
            $issueJournals = collect([$keeper]);
        }

        if ($needsPosting) {
            if ($issueJournals->isNotEmpty()) {
                $journal = $issueJournals->first();
                app(PostingService::class)->updateSaleIssueJournal($invoice, $journal);
            } else {
                $journal = app(PostingService::class)->postSaleInvoice($invoice);
                if (!$journal) {
                    throw new \RuntimeException('Invoice posting failed.');
                }
            }
        } elseif ($issueJournals->isNotEmpty()) {
            app(PostingService::class)->deleteJournalAndBalances($issueJournals->first());
        }
    }
}

