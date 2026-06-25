<?php

namespace App\Jobs;

use App\Mail\MailManager;
use App\Models\RepairIssue;
use App\Models\RepairIssueContractorAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class SendRepairQuoteRequestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $repairIssueId,
        public int $assignmentId
    ) {
    }

    public function handle(): void
    {
        $repairIssue = RepairIssue::with(['property', 'repairCategory', 'repairPhotos'])
            ->find($this->repairIssueId);
        $assignment = RepairIssueContractorAssignment::with('contractor')
            ->find($this->assignmentId);

        if (! $repairIssue || ! $assignment || ! $assignment->contractor?->email) {
            Log::warning('Repair quote request email skipped', [
                'repair_issue_id' => $this->repairIssueId,
                'assignment_id' => $this->assignmentId,
            ]);
            return;
        }

        $contractor = $assignment->contractor;
        $quoteUrl = URL::temporarySignedRoute(
            'repair-quotes.show',
            now()->addDays(14),
            ['assignment' => $assignment->id, 'token' => $assignment->quote_token]
        );
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        $pdf = Pdf::loadView('backend.repair.pdf.scope_of_work', [
            'repairIssue' => $repairIssue,
        ], [], ['format' => 'A4', 'tempDir' => $tempDir]);

        Mail::to($contractor->email)->send(new MailManager([
            'subject' => 'Quote request for repair ' . $repairIssue->reference_number,
            'content' => view('emails.repair_quote_request', compact('repairIssue', 'contractor', 'quoteUrl'))->render(),
            'attachments' => [[
                'type' => 'data',
                'data' => $pdf->output(),
                'name' => 'scope-of-work-' . $repairIssue->reference_number . '.pdf',
                'options' => ['mime' => 'application/pdf'],
            ]],
        ]));
    }
}
