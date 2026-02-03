<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionCategory;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $transactions = Transaction::with([
            'invoice',
            'property',
            'payer',
            'payee',
            'category',
            'paymentMethod',
            'bankAccount'
        ])
        ->latest()
        ->paginate(20);

        return view('backend.transactions.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request)
    {
        $categories = TransactionCategory::where('is_active', true)->pluck('name', 'id');
        $users      = User::optionsForSelect();
        $methods    = PaymentMethod::pluck('name', 'id');
        $accounts   = BankAccount::pluck('account_name', 'id');

        // Pre-generate a transaction number (if you want a prefix specific to transactions)
        // adjust prefix as needed
        try {
            $transaction_number = generateReferenceNumber(Transaction::class, 'transaction_number', 'RESISQRETXN');
        } catch (\Throwable $e) {
            // fallback if helper fails for any reason
            $transaction_number = null;
        }

        // If called from invoice page, pass invoice id to preselect
        $selectedInvoiceId = $request->query('invoice_id');

        return view('backend.transactions.create', compact(
            'categories', 'users', 'methods', 'accounts', 'transaction_number', 'selectedInvoiceId'
        ));
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date'       => 'nullable|date',
            'payment_method_id'      => 'nullable|exists:payment_methods,id',
            'bank_account_id'        => 'nullable|exists:bank_accounts,id',
            'transaction_number'     => 'nullable|string|max:255|unique:transactions,transaction_number',
            'transaction_type'       => 'required|string|in:credit,debit',
            'invoice_id'             => 'nullable|exists:invoices,id',
            'transaction_category_id'=> 'required|exists:transaction_categories,id',
            'property_id'            => 'nullable|exists:properties,id',
            'payer_id'               => 'nullable|exists:users,id',
            'payee_id'               => 'nullable|exists:users,id',
            'amount'                 => 'required|numeric|min:0',
            'transaction_reference'  => 'nullable|string|max:255',
            'status'                 => 'required|string|in:pending,completed,cancelled',
            'notes'                  => 'nullable|string',
        ]);

        // total is just amount (user removed tax); keep defensive server-side calculation
        $amount = (float) $validated['amount'];

        if (!empty($validated['invoice_id'])) {
            $invoice = Invoice::find($validated['invoice_id']);
            $outstanding = $invoice->outstandingAmount();
            if ($amount > $outstanding) {
                return back()->withErrors(['amount' => "Amount ({$amount}) exceeds invoice outstanding amount ({$outstanding})."])->withInput();
            }
            if (empty($validated['property_id']) && !empty($invoice->property_id)) {
                $validated['property_id'] = $invoice->property_id;
            }
        }

        // ensure transaction number
        if (empty($validated['transaction_number'])) {
            $validated['transaction_number'] = generateReferenceNumber(Transaction::class, 'transaction_number', 'RESISQRETXN');
        }

        $validated['bank_account_id'] = $validated['bank_account_id'] ?? null;

        // create transaction record (no credit/debit/balance)
        $transaction = DB::transaction(function () use ($validated, $amount) {
            $payload = $validated;
            $payload['amount'] = $amount;
            if (empty($payload['transaction_date'])) {
                $payload['transaction_date'] = $payload['date'] ?? now()->toDateString();
            }

            $txn = Transaction::create($payload);
            // Example: update BankAccount->balance if column exists
            /*if (!empty($payload['bank_account_id'])) {
                $bank = BankAccount::where('id', $payload['bank_account_id'])->lockForUpdate()->first();
                if ($bank) {
                    // interpret transaction_type relative to this bank's balance
                    if (($payload['transaction_type'] ?? '') === 'credit') {
                        $bank->balance = ($bank->balance ?? 0) + $amount;
                    } else {
                        $bank->balance = ($bank->balance ?? 0) - $amount;
                    }
                    $bank->save();
                }
            }*/
            // Apply payment to invoice if present and completed
            if (!empty($payload['invoice_id']) && ($payload['status'] ?? '') === 'completed') {
                $inv = Invoice::find($payload['invoice_id']);
                if ($inv) {
                    $inv->applyPayment((float)$txn->amount);
                }
            }

            return $txn;
        });

        return redirect()
            ->route('backend.transactions.index')
            ->with('success', 'Transaction created successfully.');
    }


    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        $transaction->load([
            'invoice',
            'property',
            'payer',
            'payee',
            'category',
            'paymentMethod',
            'bankAccount'
        ]);

        return view('backend.transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Transaction $transaction)
    {
        $categories = TransactionCategory::where('is_active', true)->pluck('name', 'id');
        $users      = User::pluck('name', 'id');
        $methods    = PaymentMethod::pluck('name', 'id');
        $accounts   = BankAccount::pluck('account_name', 'id');

        return view('backend.transactions.edit', compact('transaction', 'categories', 'users', 'methods', 'accounts'));
    }

    /**
     * Update the specified transaction in storage.
    */
    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'payment_method_id'       => 'nullable|exists:payment_methods,id',
            'bank_account_id'         => 'nullable|exists:bank_accounts,id',
            'transaction_number'      => 'nullable|string|max:255|unique:transactions,transaction_number,' . $transaction->id,
            'transaction_type'        => 'required|string|in:credit,debit',
            'invoice_id'              => 'nullable|exists:invoices,id',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'property_id'             => 'nullable|exists:properties,id',
            'payer_id'                => 'nullable|exists:users,id',
            'payee_id'                => 'nullable|exists:users,id',
            'amount'                  => 'required|numeric|min:0',
            'transaction_reference'   => 'nullable|string|max:255',
            'status'                  => 'required|string|in:pending,completed,cancelled',
            'notes'                   => 'nullable|string',
        ]);
        // inside controller update()
        $isFinal = $transaction->status === 'completed' && $transaction->invoice_id;

        // detect attempted money/core changes
        $moneyChanged = false;
        if ($isFinal) {
            if (isset($validated['amount']) && (float)$validated['amount'] !== (float)$transaction->amount) $moneyChanged = true;
            if (isset($validated['transaction_type']) && $validated['transaction_type'] !== $transaction->transaction_type) $moneyChanged = true;
            if (isset($validated['invoice_id']) && $validated['invoice_id'] != $transaction->invoice_id) $moneyChanged = true;
            if (isset($validated['bank_account_id']) && $validated['bank_account_id'] != $transaction->bank_account_id) $moneyChanged = true;
            if (isset($validated['transaction_number']) && $validated['transaction_number'] != $transaction->transaction_number) $moneyChanged = true;
        }

        if ($moneyChanged) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'You cannot change amount/type/invoice/account of a completed invoice payment. Create an adjustment transaction instead.']);
        }

        $transaction->update($validated);

        return redirect()
            ->route('backend.transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }


    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()
            ->route('backend.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
