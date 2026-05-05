<?php

namespace App\Jobs;

use App\Mail\MailManager;
use App\Models\NotificationLog;
use App\Models\SysSaleInvoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public NotificationLog $notificationLog)
    {
        $this->tries = (int) ($notificationLog->max_attempts ?? $this->tries);
    }

    public function handle(): void
    {
        $log = $this->notificationLog->fresh();

        if (! $log) {
            return;
        }

        if ($log->status === 'sent') {
            return;
        }

        if ($log->attempt >= $log->max_attempts) {
            return;
        }

        $log->attempt = (int) $log->attempt + 1;
        $log->last_attempt_at = now();
        $log->save();

        try {
            match ($log->channel) {
                'email' => $this->sendEmail($log),
                'sms' => $this->sendSms($log),
                'whatsapp' => $this->sendWhatsapp($log),
                'system' => $this->sendSystem($log),
                default => throw new \RuntimeException("Unsupported notification channel: {$log->channel}"),
            };

            $log->status = 'sent';
            $log->sent_at = now();
            $log->error = null;
            $log->save();
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error = $e->getMessage();
            $log->save();

            throw $e;
        }
    }

    protected function sendEmail(NotificationLog $log): void
    {
        if (empty($log->recipient)) {
            throw new \RuntimeException('Missing email recipient.');
        }

        $attachments = [];
        if (($log->payload['attach_invoice_pdf'] ?? false) && !empty($log->payload['invoice_id'])) {
            try {
                $attachments[] = $this->buildSaleInvoicePdfAttachment((int) $log->payload['invoice_id']);
            } catch (\Throwable $e) {
                Log::warning('Sale invoice PDF attachment could not be built for notification email.', [
                    'invoice_id' => $log->payload['invoice_id'],
                    'notification_log_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Mail::to($log->recipient)->send(new MailManager([
            'subject' => $log->subject ?? '',
            'content' => $log->message,
            'attachments' => $attachments,
        ]));
    }

    protected function buildSaleInvoicePdfAttachment(int $invoiceId): array
    {
        $invoice = SysSaleInvoice::with([
            'items',
            'invoiceHeader',
            'payments.bankAccount',
            'payments.paymentMethod',
        ])->findOrFail($invoiceId);

        $customer = User::find($invoice->user_id);

        $subtotal = 0;
        $taxTotal = 0;
        foreach ($invoice->items as $row) {
            $lineBase = max(0, ($row->quantity * $row->rate) - ($row->discount ?? 0));
            $subtotal += $lineBase;
            $taxTotal += (float) ($row->tax_amount ?? 0);
        }
        $total = $subtotal + $taxTotal;
        $paid = $invoice->payments->sum('amount');
        $balance = $invoice->balance_amount ?? max(0, $total - $paid);

        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        $pdf = Pdf::loadView(
            'backend.accounting.sale.invoices.pdf',
            compact('invoice', 'customer', 'subtotal', 'taxTotal', 'total', 'paid', 'balance'),
            [],
            ['format' => 'A4', 'tempDir' => $tempDir]
        );

        return [
            'type' => 'data',
            'data' => $pdf->output(),
            'name' => 'invoice-' . ($invoice->invoice_no ?? $invoice->id) . '.pdf',
            'options' => ['mime' => 'application/pdf'],
        ];
    }

    protected function sendSms(NotificationLog $log): void
    {
        if (empty($log->recipient)) {
            throw new \RuntimeException('Missing SMS recipient.');
        }

        // Mock SMS sending
        Log::info('Mock SMS sent.', [
            'to' => $log->recipient,
            'identifier' => $log->identifier,
            'notification_log_id' => $log->id,
        ]);
    }

    protected function sendWhatsapp(NotificationLog $log): void
    {
        if (empty($log->recipient)) {
            throw new \RuntimeException('Missing WhatsApp recipient.');
        }

        // Mock WhatsApp sending
        Log::info('Mock WhatsApp sent.', [
            'to' => $log->recipient,
            'identifier' => $log->identifier,
            'notification_log_id' => $log->id,
        ]);
    }

    protected function sendSystem(NotificationLog $log): void
    {
        // In-app/system notifications are considered delivered once logged.
    }
}
