@php
    /** @var \App\Models\BankDetails|null $rentBank */
    /** @var \App\Models\RentInvoice|null $invoice */
    $paymentReference = isset($invoice) && $invoice
        ? ($invoice->invoice_no ?: ('Invoice #'.$invoice->id))
        : null;
@endphp
@if($rentBank)
    <div class="tp-card mt-3" data-bank-transfer="1">
        <h3 class="h5 mb-2">Pay by bank transfer</h3>
        @if($paymentReference)
            <p class="mb-1"><strong>Payment reference: {{ $paymentReference }}</strong></p>
            <p class="tp-muted mb-3">Put that reference on the transfer. Your landlord matches it to this invoice and marks it paid when the money clears.</p>
        @else
            <p class="tp-muted mb-3">Use these details and put the invoice number from the bill you are paying as the payment reference. Your landlord will mark that invoice paid when the transfer clears.</p>
        @endif
        <dl class="tp-invoice-grid mb-0">
            <div>
                <p class="tp-muted mb-1">Bank</p>
                <p class="mb-0">{{ $rentBank->bank_name }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Account name</p>
                <p class="mb-0">{{ $rentBank->account_name }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Sort code</p>
                <p class="mb-0">{{ $rentBank->sort_code }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Account number</p>
                <p class="mb-0">{{ $rentBank->account_no }}</p>
            </div>
            @if($rentBank->swift_code)
                <div>
                    <p class="tp-muted mb-1">SWIFT / BIC</p>
                    <p class="mb-0">{{ $rentBank->swift_code }}</p>
                </div>
            @endif
        </dl>
    </div>
@else
    <div class="tp-banner mt-3" data-bank-transfer-missing="1">
        Card payment is unavailable and your landlord has not published bank transfer details yet. Message them before you pay elsewhere.
    </div>
@endif
