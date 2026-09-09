<?php

namespace App\Jobs;

use App\Mail\MailManager;
use App\Models\NotificationLog;
use App\Models\SysSaleInvoice;
use App\Models\User;
use App\Models\RepairIssue;
use App\Models\WorkOrder;
use App\Models\TaxRates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Support\AccountMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public array $backoff = [60, 300, 900];

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
        if (($log->payload['attachment_type'] ?? null) === 'repair_scope' && ! empty($log->payload['repair_issue_id'])) {
            try {
                $attachments[] = $this->buildRepairScopeAttachment((int) $log->payload['repair_issue_id']);
            } catch (\Throwable $e) {
                Log::warning('Repair scope PDF attachment could not be built.', ['notification_log_id' => $log->id, 'error' => $e->getMessage()]);
            }
        }
        if (($log->payload['attachment_type'] ?? null) === 'work_order' && ! empty($log->payload['work_order_id'])) {
            try {
                $attachments[] = $this->buildWorkOrderAttachment((int) $log->payload['work_order_id']);
            } catch (\Throwable $e) {
                Log::warning('Work order PDF attachment could not be built.', ['notification_log_id' => $log->id, 'error' => $e->getMessage()]);
            }
        }

        Mail::to($log->recipient)->send(new MailManager([
            'subject' => $log->subject ?? '',
            'content' => $log->message,
            'attachments' => $attachments,
            'reply_to' => $log->payload['reply_to'] ?? null,
            'reply_name' => $log->payload['reply_name'] ?? null,
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

    protected function buildRepairScopeAttachment(int $repairIssueId): array
    {
        $repairIssue = RepairIssue::with(['property', 'repairCategory', 'repairPhotos'])->findOrFail($repairIssueId);
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);
        $pdf = Pdf::loadView('backend.repair.pdf.scope_of_work', compact('repairIssue'), [], ['format' => 'A4', 'tempDir' => $tempDir]);

        return [
            'type' => 'data', 'data' => $pdf->output(),
            'name' => 'scope-of-work-'.($repairIssue->reference_number ?: $repairIssue->id).'.pdf',
            'options' => ['mime' => 'application/pdf'],
        ];
    }

    protected function buildWorkOrderAttachment(int $workOrderId): array
    {
        $workorder = WorkOrder::with([
            'items', 'jobType', 'jobSubType', 'repairIssue.finalContractor', 'repairIssue.property.creator',
            'repairIssue.repairCategory', 'repairIssue.tenant',
        ])->findOrFail($workOrderId);
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);
        $pdf = Pdf::loadView('backend.work_orders.work_order_pdf', [
            'workorder' => $workorder, 'taxRates' => TaxRates::all(), 'direction' => 'ltr',
            'text_align' => 'left', 'not_text_align' => 'right',
        ], [], ['format' => 'A4', 'tempDir' => $tempDir]);

        return [
            'type' => 'data', 'data' => $pdf->output(),
            'name' => 'work-order-'.($workorder->works_order_no ?: $workorder->id).'.pdf',
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
        $notifiable = $log->notifiable;
        if (! $notifiable) {
            throw new \RuntimeException('Missing in-app notification recipient.');
        }

        $uuid = $log->notification_uuid ?: (string) Str::uuid();
        $payload = $log->payload ?: [];

        DB::table('notifications')->updateOrInsert(
            ['id' => $uuid],
            [
                'account_id' => $log->account_id,
                'type' => 'crm_event',
                'event_key' => $log->identifier,
                'category' => $payload['category'] ?? null,
                'priority' => $payload['priority'] ?? 'normal',
                'action_url' => $payload['action_url'] ?? null,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => $notifiable->getKey(),
                'data' => json_encode([
                    'event_key' => $log->identifier,
                    'title' => $log->subject,
                    'message' => strip_tags((string) $log->message),
                    'url' => $payload['action_url'] ?? null,
                    'category' => $payload['category'] ?? null,
                    'priority' => $payload['priority'] ?? 'normal',
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                ], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (! $log->notification_uuid) {
            $log->forceFill(['notification_uuid' => $uuid])->save();
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $log = $this->notificationLog->fresh();
        if (! $log || ! $log->account_id || $log->identifier === 'system.delivery_failed') {
            return;
        }

        $adminIds = DB::table('account_users')
            ->where('account_id', $log->account_id)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_ADMIN_TYPES)
            ->pluck('user_id');
        $adminIds->push(DB::table('accounts')->where('id', $log->account_id)->value('owner_user_id'));

        foreach ($adminIds->filter()->unique() as $userId) {
            $uuid = (string) Str::uuid();
            DB::table('notifications')->insert([
                'id' => $uuid,
                'account_id' => $log->account_id,
                'type' => 'crm_event',
                'event_key' => 'system.delivery_failed',
                'category' => 'system',
                'priority' => 'critical',
                'action_url' => route('backend.notifications.deliveries'),
                'notifiable_type' => User::class,
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'event_key' => 'system.delivery_failed',
                    'title' => 'Notification delivery failed',
                    'message' => "{$log->identifier} could not be delivered after all attempts.",
                    'url' => route('backend.notifications.deliveries'),
                    'priority' => 'critical',
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
