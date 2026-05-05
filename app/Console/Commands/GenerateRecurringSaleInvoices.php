<?php

namespace App\Console\Commands;

use App\Models\SysSaleInvoice;
use App\Models\Tenancy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\Accounting\SaleInvoiceLifecycleService;
use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;

class GenerateRecurringSaleInvoices extends Command
{
    // php artisan sale-invoices:generate-recurring --master_id=1 --as-of=2026-05-30
    // (next child invoice date is used to simulate generation for testing, otherwise defaults to now)
    protected $signature = 'sale-invoices:generate-recurring
        {--master_id= : Optional master invoice id to generate only for one series}
        {--as-of= : Optional YYYY-MM-DD date to simulate generation for testing}';
    protected $description = 'Generate future instances for recurring sale invoices';

    public function handle(): int
    {
        $asOf = $this->option('as-of');
        $now = $asOf ? Carbon::parse((string) $asOf) : Carbon::now();
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
        $tenancyMoveOut = $this->resolveTenancyMoveOut($master, $masterDate);

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

        $rawOffsetDays = null;
        if (!empty($master->due_date)) {
            $rawOffsetDays = Carbon::parse($master->invoice_date)
                ->startOfDay()
                ->diffInDays(Carbon::parse($master->due_date)->startOfDay(), false);
        }

        // Legacy due_date offset (invoice-date-driven): default to 30 when missing/invalid.
        $offsetDaysLegacy = 30;
        if ($rawOffsetDays !== null && (int) $rawOffsetDays > 0) {
            $offsetDaysLegacy = (int) $rawOffsetDays;
        }

        $isMonthlyRecurrence = !empty($master->recurring_month_interval)
            || (($master->recurring_custom_unit ?? null) === 'month');
        $canDueDateDriveMonthly = $isMonthlyRecurrence
            && !empty($master->due_date)
            && $rawOffsetDays !== null
            && (int) $rawOffsetDays > 0;

        $unlimited = (bool) ($master->unlimited_cycles ?? false);
        $cyclesTotal = $unlimited ? null : (int) ($master->recurring_cycles ?? 0);
        $targetSeq = $cyclesTotal ? ($masterSeq + $cyclesTotal) : null;

        $existingChildSeqs = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $master->id)
            ->pluck('recurring_sequence')
            ->filter()
            ->toArray();
        $existingSet = array_fill_keys($existingChildSeqs, true);

        if ($canDueDateDriveMonthly) {
            $targetDay = (int) Carbon::parse($master->due_date)->startOfDay()->day;
            $monthsStep = max(1, (int) ($intervalValue ?? 1));
            $offsetDaysDerived = (int) $rawOffsetDays;
            $childDueDate = Carbon::parse($master->due_date)->startOfDay();

            for ($seq = $masterSeq + 1; ; $seq++) {
                if (! $unlimited && $targetSeq !== null && $seq > $targetSeq) {
                    break;
                }

                $previousDueDate = $childDueDate->copy();
                $childDueDate = $this->safeMonthlyDate($childDueDate, $monthsStep, $targetDay);
                $isPartialTenancyPeriod = false;
                $prorationFactor = 1.0;
                $partialDays = null;

                if ($tenancyMoveOut && $childDueDate->greaterThan($tenancyMoveOut)) {
                    if ($tenancyMoveOut->greaterThan($previousDueDate)) {
                        $fullPeriodDays = max(1, (int) $previousDueDate->diffInDays($childDueDate, false));
                        $partialDays = max(1, (int) $previousDueDate->diffInDays($tenancyMoveOut, false) + 1);
                        $prorationFactor = min(1.0, $partialDays / $fullPeriodDays);
                        $childDueDate = $tenancyMoveOut->copy();
                        $isPartialTenancyPeriod = true;
                    } else {
                        break;
                    }
                }

                if ($unlimited && $childDueDate->greaterThan($horizonEnd)) {
                    break;
                }

                // No backfill: skip only if the due date is already in the past.
                if ($childDueDate->lessThan($today)) {
                    continue;
                }

                $childInvoiceDate = $isPartialTenancyPeriod
                    ? $previousDueDate->copy()->startOfDay()
                    : $childDueDate->copy()->subDays($offsetDaysDerived)->startOfDay();

                // Generate/send only when the issue date has arrived (catch-up friendly).
                if ($childInvoiceDate->greaterThan($today)) {
                    break;
                }

                if (isset($existingSet[$seq])) {
                    continue; // already generated
                }

                $child = $this->createChildInvoice(
                    $master,
                    $seq,
                    $childInvoiceDate,
                    $offsetDaysDerived,
                    $childDueDate,
                    $prorationFactor,
                    $partialDays,
                    $previousDueDate,
                    $childDueDate
                );
                if ($child) {
                    $this->sendInvoiceEmailOnGeneration($child);
                }

                if ($isPartialTenancyPeriod) {
                    break;
                }
            }

            return;
        }

        if ($isMonthlyRecurrence && empty($master->due_date)) {
            $this->warn("Master invoice {$master->id} has monthly recurrence but missing due_date; falling back to legacy invoice-date-driven generation.");
        } elseif ($isMonthlyRecurrence && ($rawOffsetDays === null || (int) $rawOffsetDays <= 0)) {
            $this->warn("Master invoice {$master->id} has monthly recurrence but invalid offset (due_date - invoice_date); falling back to legacy invoice-date-driven generation.");
        }

        // Legacy invoice-date-driven recurrence for non-monthly units (day/week/year).
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

            $childDueDateCandidate = $childDate->copy()->addDays($offsetDaysLegacy)->startOfDay();
            if ($unlimited && $tenancyMoveOut && $childDueDateCandidate->greaterThan($tenancyMoveOut)) {
                break;
            }

            // Only create invoices for future invoice dates (no backfill).
            if ($childDate->lessThan($today)) {
                continue;
            }

            if (isset($existingSet[$seq])) {
                continue; // already generated
            }

            $child = $this->createChildInvoice($master, $seq, $childDate, $offsetDaysLegacy);
            if ($child) {
                $this->sendInvoiceEmailOnGeneration($child);
            }
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

    private function resolveTenancyMoveOut(SysSaleInvoice $master, Carbon $masterDate): ?Carbon
    {
        $moveOutRaw = null;

        if (($master->link_to_type ?? null) === 'Tenancy' && !empty($master->link_to_id)) {
            $moveOutRaw = Tenancy::query()
                ->where('id', (int) $master->link_to_id)
                ->value('move_out');
        } elseif (
            ($master->link_to_type ?? null) === 'Property'
            && ($master->charge_to_type ?? null) === 'Tenant'
            && !empty($master->link_to_id)
            && !empty($master->charge_to_id)
        ) {
            $moveOutRaw = Tenancy::query()
                ->where('property_id', (int) $master->link_to_id)
                ->whereHas('tenantMembers', function ($q) use ($master) {
                    $q->where('user_id', (int) $master->charge_to_id);
                })
                ->where(function ($q) use ($masterDate) {
                    $q->whereNull('move_in')
                        ->orWhereDate('move_in', '<=', $masterDate->toDateString());
                })
                ->where(function ($q) use ($masterDate) {
                    $q->whereNull('move_out')
                        ->orWhereDate('move_out', '>=', $masterDate->toDateString());
                })
                ->orderByDesc('move_in')
                ->value('move_out');
        }

        if (empty($moveOutRaw)) {
            return null;
        }

        return Carbon::parse($moveOutRaw)->startOfDay();
    }

    private function safeMonthlyDate(Carbon $baseDate, int $monthsToAdd, int $targetDay): Carbon
    {
        $monthsToAdd = max(1, (int) $monthsToAdd);
        $targetDay = max(1, min(31, (int) $targetDay));

        $baseYear = (int) $baseDate->year;
        $baseMonthIndex = (int) $baseDate->month - 1; // 0..11
        $totalMonths = ($baseYear * 12) + $baseMonthIndex + $monthsToAdd;

        $year = intdiv($totalMonths, 12);
        $month = ($totalMonths % 12) + 1;

        $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->day;
        $day = min($targetDay, $lastDay);

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    private function createChildInvoice(
        SysSaleInvoice $master,
        int $seq,
        Carbon $invoiceDate,
        int $offsetDays,
        ?Carbon $dueDate = null,
        float $prorationFactor = 1.0,
        ?int $partialDays = null,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null
    ): ?SysSaleInvoice
    {
        if ($master->items->isEmpty()) {
            return null;
        }

        $service = app(SaleInvoiceLifecycleService::class);
        $invoiceNo = $service->nextInvoiceNo();
        $prorationFactor = max(0.0, min(1.0, $prorationFactor));
        $isPartialPeriod = $prorationFactor > 0 && $prorationFactor < 1;

        $itemsPayload = $master->items->map(function ($it) use ($isPartialPeriod, $prorationFactor, $partialDays, $periodStart, $periodEnd) {
            $rate = (float) ($it->rate ?? 0);
            $discount = (float) ($it->discount ?? 0);

            if ($isPartialPeriod) {
                $rate = round($rate * $prorationFactor, 2);
                $discount = round($discount * $prorationFactor, 2);
            }

            $description = $it->description ?? null;
            if (str_contains(strtolower((string) $it->item_name), 'rent') && $periodStart && $periodEnd) {
                $description = 'Rent for ' . $periodStart->toDateString() . ' to ' . $periodEnd->toDateString();
                if ($isPartialPeriod) {
                    $description .= ' (Partial tenancy period' . ($partialDays ? " - {$partialDays} day(s)" : '') . ')';
                }
            } elseif ($isPartialPeriod) {
                $description = trim((string) ($description ?? '') . ' Partial tenancy period' . ($partialDays ? " ({$partialDays} day(s))" : ''));
            }

            return [
                'item_name' => $it->item_name,
                'description' => $description,
                'quantity' => (float) ($it->quantity ?? 0),
                'rate' => $rate,
                'discount' => $discount,
                'tax_id' => $it->tax_id ?? null,
                'tax_rate' => $it->tax_rate ?? 0,
                'notes' => $it->notes ?? null,
            ];
        })->toArray();

        $childDueDateString = $dueDate ? $dueDate->toDateString() : $invoiceDate->copy()->addDays($offsetDays)->toDateString();

        $data = [
            'invoice_no' => $invoiceNo,
            'user_id' => $master->user_id,
            'invoice_header_id' => $master->invoice_header_id,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $childDueDateString,
            'reminder_days_before_due' => $master->reminder_days_before_due ?? null,
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

        return $child;
    }

    // nextInvoiceNo() and postInvoiceIfNeeded() are intentionally handled by SaleInvoiceLifecycleService.

    private function sendInvoiceEmailOnGeneration(SysSaleInvoice $invoice): void
    {
        $recipient = optional($invoice->user)->email;
        if (empty($recipient)) {
            return;
        }

        $templates = EmailTemplate::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('status', 1)
            ->get();
        if ($templates->isEmpty()) {
            return;
        }

        $existing = NotificationLog::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('channel', 'email')
            ->where('notifiable_type', $invoice->getMorphClass())
            ->where('notifiable_id', $invoice->getKey())
            ->first();
        if ($existing) {
            return;
        }

        $data = [
            'invoice_id' => (string) $invoice->id,
            'invoice_no' => (string) ($invoice->invoice_no ?? $invoice->id),
            'invoice_date' => (string) ($invoice->invoice_date ?? ''),
            'due_date' => (string) ($invoice->due_date ?? ''),
            'total_amount' => (string) ($invoice->total_amount ?? ''),
            'balance_amount' => (string) ($invoice->balance_amount ?? ''),
            'customer_name' => (string) (optional($invoice->user)->name ?? ''),
            'customer_email' => (string) (optional($invoice->user)->email ?? ''),
            'invoice_view_url' => route('backend.accounting.sale.invoices.show', $invoice->id),
            'invoice_pdf_url' => route('backend.accounting.sale.invoices.pdf', $invoice->id),
            'attach_invoice_pdf' => true,
        ];

        foreach ($templates as $template) {
            $subject = $template->subject !== null ? render_template((string) $template->subject, $data) : '';
            $message = render_template((string) ($template->default_text ?? ''), $data);

            $log = NotificationLog::create([
                'identifier' => 'sale_invoice_send',
                'notifiable_type' => $invoice->getMorphClass(),
                'notifiable_id' => $invoice->getKey(),
                'channel' => 'email',
                'recipient' => $recipient,
                'subject' => $subject,
                'message' => $message,
                'payload' => $data,
                'status' => 'pending',
                'attempt' => 0,
                'max_attempts' => (int) config('notification_system.max_attempts', 3),
            ]);

            SendNotificationJob::dispatch($log)->afterCommit();
        }
    }
}
