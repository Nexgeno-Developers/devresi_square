<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\CreditNoteRefund;
use App\Models\DebitNoteRefund;

class NoteRefundController extends Controller
{
    /**
     * Store a refund for a credit or debit note.
     *
     * expected request:
     * - note_kind: 'credit'|'debit'
     * - note_id: int
     * - refund_date: date
     * - amount: numeric
     * - payment_method_id (nullable)
     * - bank_account_id (nullable)
     * - reference (nullable)
     * - notes (nullable)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'note_kind' => 'required|in:credit,debit',
            'note_id' => 'required|integer',
            'refund_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // load the note
        if ($data['note_kind'] === 'credit') {
            $note = CreditNote::findOrFail($data['note_id']);
        } else {
            $note = DebitNote::findOrFail($data['note_id']);
        }

        // business rule: cannot refund more than remaining amount
        // Expect CreditNote / DebitNote models to have remainingAmount() helper
        if (! method_exists($note, 'remainingAmount')) {
            return back()->withInput()->withErrors(['note' => 'The selected note does not implement remainingAmount()']);
        }

        $remaining = (float) $note->remainingAmount();
        $amount = (float) $data['amount'];

        if ($amount > $remaining + 0.0001) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Refund amount exceeds note remaining amount (£'.number_format($remaining,2).')']);
        }

        DB::transaction(function () use ($data, $note, $amount) {
            // transaction number generation: use helper if available, fallback simple token
            if (function_exists('generateDocumentNumber')) {
                $txnNumber = generateDocumentNumber('refund', 'RFND');
            } else {
                $txnNumber = 'RFND-' . strtoupper(substr(bin2hex(random_bytes(4)),0,8));
            }

            if ($data['note_kind'] === 'credit') {
                CreditNoteRefund::create([
                    'credit_note_id' => $note->id,
                    'transaction_number' => $txnNumber,
                    'refund_date' => $data['refund_date'],
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'amount' => $amount,
                    'status' => 'completed',
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'processed_by' => Auth::id(),
                ]);
            } else {
                DebitNoteRefund::create([
                    'debit_note_id' => $note->id,
                    'transaction_number' => $txnNumber,
                    'refund_date' => $data['refund_date'],
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'amount' => $amount,
                    'status' => 'completed',
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'processed_by' => Auth::id(),
                ]);
            }

            // refresh note and update status to 'refunded' if fully refunded
            $note->refresh();
            if (method_exists($note, 'remainingAmount')) {
                if ($note->remainingAmount() <= 0.0001) {
                    $note->status = 'refunded';
                    $note->saveQuietly();
                }
            }
        });

        return redirect()->back()->with('success', 'Refund processed successfully.');
    }
}
