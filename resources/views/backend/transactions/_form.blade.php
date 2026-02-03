@csrf
@if(isset($transaction))
    <input type="hidden" name="id" value="{{ $transaction->id }}">
@endif
@php
    $isFinal = isset($transaction) && $transaction->status === 'completed' && !empty($transaction->invoice_id);
@endphp
@if($isFinal)
    <input type="hidden" name="transaction_type" value="{{ $transaction->transaction_type }}">
    <input type="hidden" name="invoice_id" value="{{ $transaction->invoice_id }}">
    <input type="hidden" name="bank_account_id" value="{{ $transaction->bank_account_id }}">
    <input type="hidden" name="status" value="{{ $transaction->status }}">
@endif
<style>
input[readonly], select[disabled], .select2-container--default.select2-container--disabled .select2-selection {
    background-color: var(--bs-secondary-bg);
    cursor: not-allowed;
}
</style>
{{-- Transaction Details --}}
<div class="card mb-4">
    <div class="card-header">Transaction Details</div>
    <div class="card-body row">
        <div class="col-md-6 mb-3">
            <label>Transaction Date</label>
            <input type="date" name="transaction_date" class="form-control"
                value="{{ old('transaction_date', $transaction->transaction_date ?? $transaction->date ?? \Carbon\Carbon::now()->toDateString()) }}">
        </div>
        <div class="col-md-6 mb-3">
            <label>Transaction Number</label>
            <input type="text" name="transaction_number" class="form-control"
                value="{{ old('transaction_number', $transaction->transaction_number ?? $transaction_number ?? '') }}"
                placeholder="Auto-generated if left blank">
        </div>
        <div class="col-md-4 mb-3">
            <label>Transaction Type</label>
            <select name="transaction_type" class="form-control" @if($isFinal) disabled @endif required>
                <option value="credit" {{ old('transaction_type', $transaction->transaction_type ?? '') === 'credit' ? 'selected' : '' }}>Credit</option>
                <option value="debit" {{ old('transaction_type', $transaction->transaction_type ?? '') === 'debit' ? 'selected' : '' }}>Debit</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Category</label>
            <select name="transaction_category_id" class="form-control" required>
                <option value="">-- Select Category --</option>
                @foreach($categories as $id => $name)
                    <option value="{{ $id }}" {{ old('transaction_category_id', $transaction->transaction_category_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Associations --}}
<div class="card mb-4">
    <div class="card-header">Associations</div>
    <div class="card-body row">
        <div class="col-md-6 mb-3">
            <label>Invoice (optional)</label>
            <select name="invoice_id" class="form-control" id="invoice_id" @if($isFinal) disabled @endif></select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Property (optional)</label>
            <x-backend.property-select id="property_id" name="property_id" :selected="old('property_id', $transaction->property_id ?? null)" :disabled="$isFinal" />
        </div>
        <div class="col-md-6 mb-3">
            <label>Payer (optional)</label>
            <select name="payer_id" class="form-control">
                <option value="">-- Select Payer --</option>
                @foreach($users as $id => $label)
                    <option value="{{ $id }}" {{ old('payer_id', $transaction->payer_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Payee (optional)</label>
            <select name="payee_id" class="form-control">
                <option value="">-- Select Payee --</option>
                @foreach($users as $id => $label)
                    <option value="{{ $id }}" {{ old('payee_id', $transaction->payee_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Payment Details --}}
<div class="card mb-4">
    <div class="card-header">Payment Details</div>
    <div class="card-body row">
        <div class="col-md-4 mb-3">
            <label>Payment Method</label>
            <select name="payment_method_id" class="form-control">
                <option value="">-- Select Method --</option>
                @foreach($methods as $id => $label)
                    <option value="{{ $id }}" {{ old('payment_method_id', $transaction->payment_method_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Bank Account</label>
            <select name="bank_account_id" class="form-control" @if($isFinal) disabled @endif required>
                <option value="">-- Select Bank Account --</option>
                @foreach($accounts as $id => $label)
                    <option value="{{ $id }}" {{ old('bank_account_id', $transaction->bank_account_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Reference</label>
            <input type="text" name="transaction_reference" class="form-control"
                value="{{ old('transaction_reference', $transaction->transaction_reference ?? '') }}">
        </div>
        <div class="col-md-4 mb-3">
            <label>Amount</label>
            <input type="number" step="0.01" id="amount" name="amount" class="form-control"
                value="{{ old('amount', $transaction->amount ?? '') }}" @if($isFinal) readonly @endif required>
        </div>
        <div class="col-md-4 mb-3">
            <label>Status</label>
            <select name="status" class="form-control" @if($isFinal) disabled @endif required>
                <option value="pending" {{ old('status', $transaction->status ?? '') === 'pending' ? 'selected' : '' }}>
                    Pending</option>
                <option value="completed" {{ old('status', $transaction->status ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="card mb-4">
    <div class="card-header">Notes</div>
    <div class="card-body">
        <textarea name="notes" class="form-control">{{ old('notes', $transaction->notes ?? '') }}</textarea>
    </div>
</div>

@push('scripts')
    <script>
        const isEdit = {{ isset($transaction) && $transaction->id ? 'true' : 'false' }};
        // route endpoints
        const searchUrl = "{{ route('admin.invoices.search') }}";
        const getInvoiceUrl = function (id) {
            return "{{ route('admin.invoices.json', ['invoice' => '___INVOICE_ID___']) }}".replace('___INVOICE_ID___', id);
        };
        (function () {
            // DOM elements
            const $invoice = $('#invoice_id'); // requires jQuery for select2
            const amountInput = document.getElementById('amount');
            const propertySelect = document.querySelector('select[name="property_id"]');

            // init Select2 with AJAX
            $invoice.select2({
                placeholder: '-- Select Invoice --',
                allowClear: true,
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            only_outstanding: 1 // optional: only show invoices with outstanding > 0
                        };
                    },
                    processResults: function (data) {
                        // data.results must be array of {id, text, outstanding, property_id, total_amount}
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                templateResult: function (item) {
                    if (!item.id) return item.text;
                    // show outstanding inline
                    let out = item.outstanding !== undefined ? ' — outstanding £' + (parseFloat(item.outstanding).toFixed(2)) : '';
                    return $('<span>' + item.text + out + '</span>');
                },
                templateSelection: function (item) {
                    return item.text || item.id || '';
                }
            });

            // When an invoice is selected, auto-fill amount & property & enforce max
            $invoice.on('select2:select', function (e) {
                const data = e.params.data;
                handleInvoiceSelection(data);
            });

            // When cleared
            $invoice.on('select2:clear', function () {
                // reset amount only if the transaction did not have a prefilled amount
                // clear max attribute
                if (amountInput) {
                    amountInput.removeAttribute('max');
                }
            });

            function handleInvoiceSelection(data) {
                if (!data) return;
                const outstanding = Number(data.outstanding || 0);

                if (amountInput) {
                    const current = parseFloat(amountInput.value || 0);

                    if (!isEdit) {
                        // CREATE MODE: fill from outstanding
                        if (!current || current <= 0) {
                            amountInput.value = outstanding.toFixed(2);
                        } else if (current > outstanding) {
                            amountInput.value = outstanding.toFixed(2);
                        }
                    } else {
                        // EDIT MODE: keep saved value, just enforce readonly if outstanding=0
                        if (outstanding <= 0) {
                            amountInput.setAttribute('readonly', 'readonly');
                            amountInput.classList.add('bg-light');
                        } else {
                            amountInput.removeAttribute('readonly');
                            amountInput.classList.remove('bg-light');
                        }
                    }

                    // always enforce max + dataset
                    amountInput.max = outstanding;
                    amountInput.dataset.outstanding = outstanding;
                }
                // preselect property if present and nothing selected
                /*if (data.property_id && propertySelect) {
                    if (!propertySelect.value || propertySelect.value == '') {
                        propertySelect.value = data.property_id;
                    }
                }*/
                if (data.property_id && propertySelect) {
                    if (!propertySelect.value || propertySelect.value == '') {
                        preselectProperty(propertySelect, data.property_id);
                    }
                }
            }

            // preselect if requested (server passed selectedInvoiceId)
            @if(!empty($selectedInvoiceId) || !empty($transaction->invoice_id))
                (function () {
                    const id = "{{ $selectedInvoiceId ?? $transaction->invoice_id }}";
                    // fetch invoice data and set as Select2 initial value
                    $.ajax({
                        url: getInvoiceUrl(id),
                        type: 'GET',
                        dataType: 'json'
                    }).done(function (data) {
                        if (!data) return;
                        // create option and select it
                        const option = new Option(data.text, data.id, true, true);
                        // attach extra details for later access
                        option.dataset.outstanding = data.outstanding;
                        option.dataset.property_id = data.property_id;
                        $invoice.append(option).trigger('change');

                        // call handler to fill amount/property
                        handleInvoiceSelection(data);
                    });
                })();
            @endif

            // final client-side guard on submit (prevents going beyond outstanding)
            const form = $invoice.closest('form')[0];
            if (form) {
                form.addEventListener('submit', function (e) {
                    const selected = $invoice.select2('data')[0];
                    if (selected && selected.outstanding !== undefined) {
                        const outstanding = Number(selected.outstanding || 0);
                        const val = parseFloat(amountInput.value || 0);
                        if (val > outstanding) {
                            e.preventDefault();
                            alert('Amount cannot exceed invoice outstanding amount (£' + outstanding.toFixed(2) + ').');
                            return false;
                        }
                    }
                });
            }

            // function to preselect property in the property select2 component
            function preselectProperty(propertySelect, propertyId) {
                if (!propertySelect || !propertyId) return;
                const $propertySelect = $(propertySelect);

                // If property not already selected
                if (!$propertySelect.val()) {
                    // fetch property details (to get display_label)
                    $.ajax({
                        url: '{{ route("backend.properties.search-ajax") }}',
                        data: { q: propertyId },
                        dataType: 'json'
                    }).done(function (resp) {
                        let property = resp.find(p => p.id == propertyId);
                        if (property) {
                            let option = new Option(property.display_label, property.id, true, true);
                            $propertySelect.append(option).trigger('change');
                        }
                    });
                }
            }
        })();
    </script>
@endpush