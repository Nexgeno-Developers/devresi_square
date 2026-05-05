<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\SysSaleInvoice;
use Illuminate\Console\Command;

class SendUnsentSaleInvoiceEmails extends Command
{
    protected $signature = 'sale-invoices:send-unsent-emails
        {--invoice_id= : Queue email only for one invoice id}
        {--master_id= : Queue emails for a recurring master and its children}
        {--force : Queue a new email even if an invoice send log already exists}
        {--limit=1000 : Maximum invoices to scan}';

    protected $description = 'Queue missing sale invoice send emails for existing invoices.';

    public function handle(): int
    {
        $templates = EmailTemplate::query()
            ->where('identifier', 'sale_invoice_send')
            ->where('status', 1)
            ->get();

        if ($templates->isEmpty()) {
            $this->warn("No active email_templates found for identifier 'sale_invoice_send'.");
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $invoiceId = $this->option('invoice_id');
        $masterId = $this->option('master_id');
        $force = (bool) $this->option('force');

        $query = SysSaleInvoice::query()
            ->where('status', '!=', 'cancelled')
            ->with('user')
            ->orderBy('id')
            ->limit($limit);

        if ($invoiceId) {
            $query->where('id', (int) $invoiceId);
        }

        if ($masterId) {
            $masterId = (int) $masterId;
            $query->where(function ($q) use ($masterId) {
                $q->where('id', $masterId)
                    ->orWhere('recurring_master_invoice_id', $masterId);
            });
        }

        $created = 0;
        $skipped = 0;

        foreach ($query->get() as $invoice) {
            $recipient = optional($invoice->user)->email;
            if (empty($recipient)) {
                $this->warn("Skipping invoice {$invoice->id}: missing recipient email.");
                $skipped++;
                continue;
            }

            $existing = NotificationLog::query()
                ->where('identifier', 'sale_invoice_send')
                ->where('channel', 'email')
                ->where('notifiable_type', $invoice->getMorphClass())
                ->where('notifiable_id', $invoice->getKey())
                ->first();

            if ($existing && ! $force) {
                $skipped++;
                continue;
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
                $created++;
            }
        }

        $this->info("Done. Created: {$created}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
