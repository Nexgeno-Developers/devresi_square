<?php

namespace App\Console\Commands;

use App\Models\SysSaleInvoice;
use App\Services\Accounting\SaleInvoiceLifecycleService;
use App\Services\Accounting\SaleInvoicePenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ApplySaleInvoicePenalties extends Command
{
    protected $signature = 'sale-invoices:apply-penalties {--chunk=100}';
    protected $description = 'Apply late-payment penalties to overdue sale invoices (idempotent)';

    public function handle(): int
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $chunk = (int) ($this->option('chunk') ?? 100);
        $chunk = max(1, $chunk);

        $query = SysSaleInvoice::query()
            ->where('penalty_enabled', true)
            ->whereNull('penalty_applied_at')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString());

        $appliedCount = 0;
        $skippedCount = 0;

        $query->chunk($chunk, function ($invoices) use (&$appliedCount, &$skippedCount, $now) {
            foreach ($invoices as $invoice) {
                $applied = app(SaleInvoicePenaltyService::class)->applyPenaltyIfEligible($invoice, $now);
                if ($applied) {
                    $invoice->refresh();
                    app(SaleInvoiceLifecycleService::class)->postInvoiceIfNeeded($invoice);
                    $appliedCount++;
                } else {
                    $skippedCount++;
                }
            }
        });

        $this->info("Penalty apply finished. Applied: {$appliedCount}, Skipped: {$skippedCount}.");

        return self::SUCCESS;
    }
}

