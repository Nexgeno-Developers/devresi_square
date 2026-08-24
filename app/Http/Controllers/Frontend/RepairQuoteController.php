<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\RepairIssueContractorAssignment;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Enums\CrmNotificationEvent;
use App\Services\Notifications\CrmNotificationService;

class RepairQuoteController extends Controller
{
    public function show(Request $request, RepairIssueContractorAssignment $assignment, string $token)
    {
        abort_unless(hash_equals((string) $assignment->quote_token, $token), 403);

        $assignment->load(['repairIssue.property', 'repairIssue.repairCategory', 'repairIssue.repairPhotos', 'contractor']);
        $submitUrl = URL::temporarySignedRoute(
            'repair-quotes.submit',
            now()->addDays(14),
            ['assignment' => $assignment->id, 'token' => $token]
        );

        return view('frontend.repair_quotes.show', compact('assignment', 'submitUrl'));
    }

    public function submit(Request $request, RepairIssueContractorAssignment $assignment, string $token)
    {
        abort_unless(hash_equals((string) $assignment->quote_token, $token), 403);

        $validated = $request->validate([
            'estimated_price' => 'required|numeric|min:0',
            'availability' => 'nullable|string',
            'contractor_preferred_availability' => 'nullable|date',
            'consultant_name' => 'required|string|max:255',
            'consultant_phone' => 'required|string|max:50',
            'tentative_start_date' => 'nullable|date',
            'tentative_end_date' => 'nullable|date|after_or_equal:tentative_start_date',
            'quote_notes' => 'nullable|string',
            'quote_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $availabilityOptions = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['availability'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $quoteAttachmentId = $assignment->quote_attachment;
        if ($request->hasFile('quote_attachment')) {
            $quoteAttachmentId = Upload::storeFile($request->file('quote_attachment'))->id;
        }

        $assignment->update([
            'cost_price' => $validated['estimated_price'],
            'contractor_availability_options' => $availabilityOptions ?: null,
            'contractor_preferred_availability' => $validated['contractor_preferred_availability'] ?? null,
            'consultant_name' => $validated['consultant_name'],
            'consultant_phone' => $validated['consultant_phone'],
            'tentative_start_date' => $validated['tentative_start_date'] ?? null,
            'tentative_end_date' => $validated['tentative_end_date'] ?? null,
            'quote_notes' => $validated['quote_notes'] ?? null,
            'quote_attachment' => $quoteAttachmentId,
            'quote_submitted_at' => now(),
            'status' => 'Quoted',
        ]);

        $repairIssue = $assignment->repairIssue()->with('property')->first();
        if ($repairIssue) {
            app(CrmNotificationService::class)->dispatch(
                CrmNotificationEvent::RepairQuoteSubmitted,
                $repairIssue,
                [
                    'account_id' => $repairIssue->account_id,
                    'repair_reference' => $repairIssue->reference_number,
                    'property_address' => $repairIssue->property?->full_address,
                    'action_url' => route('admin.property_repairs.show', $repairIssue->id),
                    'milestone' => 'quote-submitted-'.$assignment->id.'-'.$assignment->updated_at?->timestamp,
                ],
            );
        }

        return redirect()
            ->route('repair-quotes.show', ['assignment' => $assignment->id, 'token' => $token, 'signature' => $request->query('signature'), 'expires' => $request->query('expires')])
            ->with('success', 'Your quote has been submitted successfully.');
    }
}
