<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\NoteApplication;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;

class AccountsNoteApplicationController extends Controller
{
    /**
     * Apply a note to an invoice.
     *
     * Request expects:
     * - note_type: 'credit'|'debit'
     * - note_id
     * - applied_to_type: fully-qualified model class name (Invoice::class or PurchaseInvoice::class)
     * - applied_to_id
     * - applied_amount
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'note_kind' => 'required|in:credit,debit',
            'note_id' => 'required|integer',
            'applied_to_type' => 'required|string',
            'applied_to_id' => 'required|integer',
            'applied_amount' => 'required|numeric|min:0.01',
        ]);

        // load note
        $note = $data['note_kind'] === 'credit'
            ? CreditNote::findOrFail($data['note_id'])
            : DebitNote::findOrFail($data['note_id']);

        // load applied_to model safely
        $allowedApplyModels = [
            Invoice::class,
            PurchaseInvoice::class,
        ];

        if (! in_array($data['applied_to_type'], $allowedApplyModels, true)) {
            return back()->withInput()->withErrors(['applied_to_type' => 'Invalid target type']);
        }

        $appliedTo = $data['applied_to_type']::findOrFail($data['applied_to_id']);

        // enforce that note party matches invoice party
        if ($data['applied_to_type'] === Invoice::class) {
            // invoice->user_id should match note->party_id when note is client-related credit/debit for sales
            if ($note->party_role === 'client' && $appliedTo->user_id != $note->party_id) {
                return back()->withInput()->withErrors(['applied_to' => 'Invoice party mismatch']);
            }
        } else {
            // purchase invoice -> supplier
            if ($note->party_role === 'vendor' && $appliedTo->supplier_id != $note->party_id) {
                return back()->withInput()->withErrors(['applied_to' => 'Purchase invoice party mismatch']);
            }
        }

        // ensure not exceeding remaining
        $remaining = $note->remainingAmount();
        $apply = (float) $data['applied_amount'];
        if ($apply > $remaining + 0.0001) {
            return back()->withInput()->withErrors(['applied_amount' => 'Applied amount exceeds remaining note amount']);
        }

        DB::transaction(function () use ($note, $data, $appliedTo, $apply) {
            $note->applications()->create([
                'applied_amount' => $apply,
                'applied_to_id' => $data['applied_to_id'],
                'applied_to_type' => $data['applied_to_type'],
                'applied_by' => Auth::id(),
                'applied_at' => now(),
            ]);

            // update note status if fully used
            if ($note->remainingAmount() - $apply <= 0.0001) {
                $note->status = 'applied';
                $note->saveQuietly();
            }

            // optionally update invoice status (if invoice model has updateInvoiceStatus method)
            if (method_exists($appliedTo, 'updateInvoiceStatus')) {
                $appliedTo->updateInvoiceStatus();
            } elseif (method_exists($appliedTo, 'save')) {
                // touch to trigger any observers
                $appliedTo->saveQuietly();
            }
        });

        return redirect()->back()->with('success','Note applied successfully');
    }
}
