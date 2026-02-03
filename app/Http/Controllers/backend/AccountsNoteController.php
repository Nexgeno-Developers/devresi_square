<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountsNoteController extends Controller
{
    // show form to create credit note
    public function createCredit()
    {
        return view('backend.notes.create_credit');
    }

    public function storeCredit(Request $request)
    {
        $data = $request->validate([
            'party_id' => 'required|exists:users,id',
            'party_role' => 'required|in:client,vendor',
            'note_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $noteNumber = function_exists('generateDocumentNumber')
            ? generateDocumentNumber('credit_note','CRN')
            : DocumentSequence::generate('credit_note','CRN');

        $note = CreditNote::create(array_merge($data, [
            'note_number' => $noteNumber,
            'created_by' => Auth::id(),
        ]));

        return redirect()->route('backend.credit_notes.show', $note->id)->with('success','Credit note created');
    }

    public function createDebit()
    {
        return view('backend.notes.create_debit');
    }

    public function storeDebit(Request $request)
    {
        $data = $request->validate([
            'party_id' => 'required|exists:users,id',
            'party_role' => 'required|in:client,vendor',
            'note_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $noteNumber = function_exists('generateDocumentNumber')
            ? generateDocumentNumber('debit_note','DBN')
            : DocumentSequence::generate('debit_note','DBN');

        $note = DebitNote::create(array_merge($data, [
            'note_number' => $noteNumber,
            'created_by' => Auth::id(),
        ]));

        return redirect()->route('backend.debit_notes.show', $note->id)->with('success','Debit note created');
    }

    public function showCredit(CreditNote $creditNote)
    {
        return view('backend.notes.show_credit', compact('creditNote'));
    }

    public function showDebit(DebitNote $debitNote)
    {
        return view('backend.notes.show_debit', compact('debitNote'));
    }
}
