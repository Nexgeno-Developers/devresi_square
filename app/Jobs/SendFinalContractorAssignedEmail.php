<?php

namespace App\Jobs;

use App\Mail\MailManager;
use App\Models\RepairIssue;
use App\Models\RepairIssueContractorAssignment;
use App\Models\TaxRates;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class SendFinalContractorAssignedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $repairIssueId,
        public int $contractorId
    ) {
    }

    public function handle(): void
    {
        $repairIssue = RepairIssue::with([
            'property',
            'repairCategory',
            'tenant',
            'workOrder.items',
            'workOrder.jobType',
            'workOrder.jobSubType',
        ])->find($this->repairIssueId);

        $contractor = User::find($this->contractorId);

        if (! $repairIssue || ! $contractor?->email) {
            Log::warning('Final contractor assignment email skipped', [
                'repair_issue_id' => $this->repairIssueId,
                'contractor_id' => $this->contractorId,
            ]);
            return;
        }

        $assignment = RepairIssueContractorAssignment::where('repair_issue_id', $repairIssue->id)
            ->where('contractor_id', $contractor->id)
            ->first();

        $attachments = [];

        try {
            $tempDir = storage_path('app/mpdf');
            File::ensureDirectoryExists($tempDir);

            $workorder = WorkOrder::with([
                'items',
                'jobType',
                'jobSubType',
                'repairIssue.finalContractor',
                'repairIssue.property.creator',
                'repairIssue.repairCategory',
                'repairIssue.tenant',
            ])->where('repair_issue_id', $repairIssue->id)->first();

            if (! $workorder) {
                throw new \RuntimeException('Work order not found for final contractor email.');
            }

            $pdf = Pdf::loadView('backend.work_orders.work_order_pdf', [
                'workorder' => $workorder,
                'taxRates' => TaxRates::all(),
                'direction' => 'ltr',
                'text_align' => 'left',
                'not_text_align' => 'right',
            ], [], ['format' => 'A4', 'tempDir' => $tempDir]);

            $attachments[] = [
                'type' => 'data',
                'data' => $pdf->output(),
                'name' => 'work-order-' . ($workorder->works_order_no ?? $repairIssue->reference_number) . '.pdf',
                'options' => ['mime' => 'application/pdf'],
            ];
        } catch (\Throwable $exception) {
            Log::warning('Final contractor assignment PDF attachment skipped', [
                'repair_issue_id' => $repairIssue->id,
                'contractor_id' => $contractor->id,
                'error' => $exception->getMessage(),
            ]);
        }

        Mail::to($contractor->email)->send(new MailManager([
            'subject' => 'Repair work assigned - ' . $repairIssue->reference_number,
            'content' => view('emails.repair_work_assigned', compact('repairIssue', 'contractor', 'assignment'))->render(),
            'attachments' => $attachments,
        ]));
    }
}
