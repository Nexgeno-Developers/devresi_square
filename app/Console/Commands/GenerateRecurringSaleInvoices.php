<?php

namespace App\Console\Commands;

use App\Models\SysSaleInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\Accounting\SaleInvoiceLifecycleService;

class GenerateRecurringSaleInvoices extends Command
{
    protected $signature = 'sale-invoices:generate-recurring {--master_id= : Optional master invoice id to generate only for one series}';
    protected $description = 'Generate future instances for recurring sale invoices';

    public function handle(): int
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $horizonEnd = $now->copy()->addMonths(12);

        $mastersQuery = SysSaleInvoice::query()
            ->whereNull('recurring_master_invoice_id')
            ->where(function ($q) {
                $q->whereNotNull('recurring_month_interval')
                    ->orWhereNotNull('recurring_custom_unit');
            })
            ->with('items')
        ;

        $masterId = $this->option('master_id');
        if ($masterId) {
            $mastersQuery->where('id', (int) $masterId);
        }

        $masters = $mastersQuery->get();

        if ($masters->isEmpty()) {
            $this->info('No recurring sale invoice masters found.');
            return self::SUCCESS;
        }

        $this->info('Generating recurring sale invoices...');

        foreach ($masters as $master) {
            $this->generateForMaster($master, $today, $horizonEnd);
        }

        $this->info('Recurring sale invoice generation completed.');
        return self::SUCCESS;
    }

    private function generateForMaster(SysSaleInvoice $master, Carbon $today, Carbon $horizonEnd): void
    {
        if (empty($master->invoice_date)) {
            $this->warn("Skipping master invoice {$master->id}: missing invoice_date");
            return;
        }

        $masterDate = Carbon::parse($master->invoice_date)->startOfDay();
        $masterSeq = (int) ($master->recurring_sequence ?? 1);

        // Due date offset from master invoice.
        $offsetDays = 30;
        if (!empty($master->due_date)) {
            $offsetDays = Carbon::parse($master->invoice_date)
                ->diffInDays(Carbon::parse($master->due_date), false);
            if ($offsetDays <= 0) {
                $offsetDays = 30;
            }
        }

        $intervalUnit = null;
        $intervalValue = null;
        if (!empty($master->recurring_month_interval)) {
            $intervalUnit = 'month';
            $intervalValue = (int) $master->recurring_month_interval;
        } elseif (!empty($master->recurring_custom_unit)) {
            $intervalUnit = $master->recurring_custom_unit;
            $intervalValue = (int) ($master->recurring_custom_interval ?? 1);
        } else {
            return;
        }

        $unlimited = (bool) ($master->unlimited_cycles ?? false);
        $cyclesTotal = $unlimited ? null : (int) ($master->recurring_cycles ?? 0);
        $targetSeq = $cyclesTotal ? ($masterSeq + $cyclesTotal - 1) : null;

        $existingChildSeqs = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $master->id)
            ->pluck('recurring_sequence')
            ->filter()
            ->toArray();
        $existingSet = array_fill_keys($existingChildSeqs, true);

        $childDate = $masterDate->copy();

        for ($seq = $masterSeq + 1; ; $seq++) {
            if (! $unlimited && $targetSeq !== null && $seq > $targetSeq) {
                break;
            }

            // Next invoice date for this sequence step.
            $childDate = $this->addInterval($childDate, $intervalUnit, $intervalValue);

            if ($unlimited && $childDate->greaterThan($horizonEnd)) {
                break;
            }

            // Only create invoices for future dates.
            if ($childDate->lessThan($today)) {
                continue;
            }

            if (isset($existingSet[$seq])) {
                continue; // already generated
            }

            $this->createChildInvoice($master, $seq, $childDate, $offsetDays);
        }
    }

    private function addInterval(Carbon $date, string $unit, int $value): Carbon
    {
        $value = max(1, $value);
        $d = $date->copy();

        return match ($unit) {
            'day' => $d->addDays($value),
            'week' => $d->addWeeks($value),
            'month' => $d->addMonths($value),
            'year' => $d->addYears($value),
            default => $d->addMonths($value),
        };
    }

    private function createChildInvoice(SysSaleInvoice $master, int $seq, Carbon $invoiceDate, int $offsetDays): void
    {
        if ($master->items->isEmpty()) {
            return;
        }

        $service = app(SaleInvoiceLifecycleService::class);
        $invoiceNo = $service->nextInvoiceNo();

        $itemsPayload = $master->items->map(function ($it) {
            return [
                'item_name' => $it->item_name,
                'description' => $it->description ?? null,
                'quantity' => (float) ($it->quantity ?? 0),
                'rate' => (float) ($it->rate ?? 0),
                'discount' => (float) ($it->discount ?? 0),
                'tax_id' => $it->tax_id ?? null,
                'tax_rate' => $it->tax_rate ?? 0,
                'notes' => $it->notes ?? null,
            ];
        })->toArray();

        $data = [
            'invoice_no' => $invoiceNo,
            'user_id' => $master->user_id,
            'invoice_header_id' => $master->invoice_header_id,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addDays($offsetDays)->toDateString(),
            'status' => $master->status ?? 'draft',
            'notes' => $master->notes ?? null,

            'link_to_type' => $master->link_to_type,
            'link_to_id' => $master->link_to_id,
            'charge_to_type' => $master->charge_to_type,
            'charge_to_id' => $master->charge_to_id,
            'bank_account_id' => $master->bank_account_id,

            // Penalty settings/inputs only (never copy applied fields)
            'penalty_enabled' => (bool) ($master->penalty_enabled ?? false),
            'penalty_type' => $master->penalty_type,
            'penalty_fixed_rate' => $master->penalty_fixed_rate,
            'penalty_gl_account_id' => $master->penalty_gl_account_id,
            'penalty_grace_days' => $master->penalty_grace_days,
            'penalty_max_amount' => $master->penalty_max_amount,

            // Recurrence metadata (duplicated for easier edit/validation on children).
            'recurring_master_invoice_id' => $master->id,
            'recurring_sequence' => $seq,
            'recurring_month_interval' => $master->recurring_month_interval,
            'recurring_custom_interval' => $master->recurring_custom_interval,
            'recurring_custom_unit' => $master->recurring_custom_unit,
            'unlimited_cycles' => (bool) ($master->unlimited_cycles ?? false),
            'recurring_cycles' => $master->recurring_cycles,

            'items' => $itemsPayload,
        ];

        $child = $service->persistItems($data, null);
        $service->postInvoiceIfNeeded($child);
    }

    // nextInvoiceNo() and postInvoiceIfNeeded() are intentionally handled by SaleInvoiceLifecycleService.
}

