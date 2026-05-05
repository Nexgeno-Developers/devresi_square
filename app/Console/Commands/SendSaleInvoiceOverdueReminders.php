<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\SysSaleInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSaleInvoiceOverdueReminders extends Command
{
    protected $signature = 'sale-invoices:send-overdue-reminders
        {--date= : YYYY-MM-DD override}
        {--as-of= : YYYY-MM-DD override}
        {--limit=1000}';

    protected $description = 'Send sale invoice overdue reminder emails (idempotent).';

    public function handle(): int
    {
        $today = $this->resolveToday();

        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 1000;
        }

        $templates = EmailTemplate::query()
            ->where('identifier', 'sale_invoice_overdue_reminder')
            ->where('status', 1)
            ->get();

        if ($templates->isEmpty()) {
            $this->warn("No active email_templates found for identifier 'sale_invoice_overdue_reminder'.");
            return self::SUCCESS;
        }

        $overdueDays = (int) get_setting('sale_invoice_overdue_days_after_due', 1);
        $overdueDays = max(0, min(365, $overdueDays));

        $windowStart = $today->copy()->subDays(min(730, 365 + $overdueDays));

        $candidates = SysSaleInvoice::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $windowStart->toDateString())
            ->whereDate('due_date', '<=', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('balance_amount')->orWhere('balance_amount', '>', 0);
            })
            ->with('user')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No sale invoices eligible for overdue reminders today.');
            return self::SUCCESS;
        }

        $created = 0;
        $requeued = 0;
        $skipped = 0;

        foreach ($candidates as $invoice) {
            $due = Carbon::parse($invoice->due_date)->startOfDay();
            $overdueOn = $due->copy()->addDays($overdueDays);

            if ($overdueOn->greaterThan($today)) {
                $skipped++;
                continue;
            }

            $recipient = optional($invoice->user)->email;
            if (empty($recipient)) {
                $this->warn("Skipping invoice {$invoice->id}: missing recipient email.");
                $skipped++;
                continue;
            }

            $existing = NotificationLog::query()
                ->where('identifier', 'sale_invoice_overdue_reminder')
                ->where('channel', 'email')
                ->where('notifiable_type', $invoice->getMorphClass())
                ->where('notifiable_id', $invoice->getKey())
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                if ($existing->status === 'failed' && (int) $existing->attempt < (int) $existing->max_attempts) {
                    SendNotificationJob::dispatch($existing)->afterCommit();
                    $requeued++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $data = $this->templateData($invoice, $overdueDays);

            foreach ($templates as $template) {
                $subject = $template->subject !== null ? render_template((string) $template->subject, $data) : '';
                $message = render_template((string) ($template->default_text ?? ''), $data);

                $log = NotificationLog::create([
                    'identifier' => 'sale_invoice_overdue_reminder',
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
                $created++;
            }
        }

        $this->info("Done. Created: {$created}, Requeued: {$requeued}, Skipped: {$skipped}.");
        return self::SUCCESS;
    }

    private function resolveToday(): Carbon
    {
        $override = $this->option('as-of') ?: $this->option('date');
        if ($override) {
            return Carbon::parse((string) $override)->startOfDay();
        }

        return Carbon::now()->startOfDay();
    }

    private function templateData(SysSaleInvoice $invoice, int $daysAfterDue): array
    {
        $user = $invoice->user;

        return [
            'invoice_id' => (string) $invoice->id,
            'invoice_no' => (string) ($invoice->invoice_no ?? $invoice->id),
            'invoice_date' => (string) ($invoice->invoice_date ?? ''),
            'due_date' => (string) ($invoice->due_date ?? ''),
            'days_after_due' => (string) $daysAfterDue,
            'total_amount' => (string) ($invoice->total_amount ?? ''),
            'balance_amount' => (string) ($invoice->balance_amount ?? ''),
            'customer_name' => (string) (optional($user)->name ?? ''),
            'customer_email' => (string) (optional($user)->email ?? ''),
        ];
    }
}
