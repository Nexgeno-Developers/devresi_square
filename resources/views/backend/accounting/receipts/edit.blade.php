@extends('backend.layout.app')

@section('content')
    @include('backend.partials.assets.select2')
    <div class="mt-md-4 me-md-4 me-3 mt-3">
        <h2 class="mb-1">Edit Receipt</h2>
        <p class="text-muted mb-4">Update receipt details and push channels.</p>

        <form action="{{ route($routeName . '.update', $item->id) }}" method="POST" id="receipt-flow-form" class="receipt-flow">
            @csrf
            @method('PUT')

            {{-- hidden wiring for the resource fields --}}
            <input type="hidden" name="receiptable_type" id="receiptable_type" value="{{ old('receiptable_type', $item->receiptable_type ?? '') }}">
            <input type="hidden" name="receiptable_id" id="receiptable_id" value="{{ old('receiptable_id', $item->receiptable_id ?? '') }}">
            <input type="hidden" name="status" value="{{ old('status', $item->status ?? 'unapplied') }}">
            <input type="hidden" name="applied_amount" value="{{ old('applied_amount', $item->applied_amount ?? 0) }}">
            <input type="hidden" name="receipt_no" value="{{ old('receipt_no', $item->receipt_no ?? '') }}">
            <input type="hidden" name="company_id" value="{{ old('company_id', $item->company_id ?? '') }}">

            @include('backend.accounting.receipts._create_flow', ['item' => $item])
        </form>
    </div>
@endsection

@push('styles')
    <style>
        .receipt-flow { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; padding: 16px; }
        .flow-grid { display: grid; gap: 16px; grid-template-columns: 1fr; }
        .flow-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; box-shadow: none; }
        .flow-card--accent { background: #fff7ed; border-color: #fcd9b6; }
        .flow-card__title { font-weight: 700; color: #111827; margin-bottom: 8px; }
        .flow-card--accent .flow-card__title { color: #9a3412; }
        .text-dim { color: #6b7280; }
        .toggle-yesno .btn { min-width: 70px; font-weight: 600; background: #fff; color: #111827; border: 1px solid #d1d5db; box-shadow: none; }
        .toggle-yesno .btn-check:checked + .btn { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .toggle-yesno .btn:hover,
        .toggle-yesno .btn:focus-visible { background: #f8f9fa; color: #111827; border-color: #ced4da; box-shadow: none; }
        .toggle-yesno .btn-check:checked + .btn:hover,
        .toggle-yesno .btn-check:checked + .btn:focus-visible { background: #0b5ed7; color: #fff; border-color: #0b5ed7; }
        .badge-outstanding { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('receipt-flow-form');
            const invoiceSelect = $('#invoice_id');
            const amountInput = document.getElementById('amount');
            const outstandingBadge = document.getElementById('outstanding-badge');
            const receiptableType = document.getElementById('receiptable_type');
            const receiptableId = document.getElementById('receiptable_id');
            const userSelect = document.getElementById('user_id');
            const notes = document.getElementById('notes');
            const paymentMethod = document.getElementById('payment_method_id');
            const methodDetails = document.getElementById('method-details');
            const methodFields = document.getElementById('method-fields');
            const methodMap = @json(($paymentMethods ?? collect())->mapWithKeys(fn($m) => [$m->id => ['code' => strtolower($m->code ?? ''), 'name' => $m->name]])->toArray());
            const metaInitial = @json(old('payment_meta', $item->payment_meta ?? []));
            const poundSymbol = "{{ getPoundSymbol() }}";

            const currentSelections = {
                user: "{{ old('user_id', $item->user_id) }}",
                bank: "{{ old('sys_bank_account_id', $item->sys_bank_account_id) }}",
                method: "{{ old('payment_method_id', $item->payment_method_id) }}",
                receiptableId: "{{ old('receiptable_id', $item->receiptable_id) }}",
                receiptableType: "{{ old('receiptable_type', $item->receiptable_type) }}"
            };

            const select2SearchUrl = "{{ route('backend.accounting.sale.invoices.search') }}";
            const invoiceJsonUrl = (id) => "{{ route('backend.accounting.sale.invoices.json', ['invoice' => '___ID___']) }}".replace('___ID___', id);

            invoiceSelect.select2({
                placeholder: 'Search invoice number or customer',
                allowClear: true,
                ajax: {
                    url: select2SearchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term, only_outstanding: 1 }),
                    processResults: data => ({
                        results: (data.results || []).map(row => ({
                            id: row.id,
                            text: row.text,
                            outstanding: row.outstanding,
                            user_id: row.user_id,
                            user_name: row.user_name
                        }))
                    })
                },
                templateResult: function (item) {
                    if (!item.id) return item.text;
                    const out = item.outstanding !== undefined ? ` • Outstanding ${poundSymbol}${Number(item.outstanding).toFixed(2)}` : '';
                    const user = item.user_name ? ` • ${item.user_name}` : '';
                    return $('<span>').text(item.text + out + user);
                },
                templateSelection: function (item) {
                    return item.text || item.id;
                },
                minimumInputLength: 1
            });

            // show existing invoice in Select2
            if (currentSelections.receiptableId) {
                invoiceSelect.val(currentSelections.receiptableId).trigger('change');
                if (currentSelections.receiptableType === 'sale_invoice') {
                    receiptableType.value = 'sale_invoice';
                    receiptableId.value = currentSelections.receiptableId;
                }
            }

            enforceSelectDefaults();
            if (currentSelections.receiptableId) {
                const selectedOpt = document.querySelector(`#invoice_id option[value="${currentSelections.receiptableId}"]`);
                if (selectedOpt) {
                    setInvoiceContext({
                        id: currentSelections.receiptableId,
                        outstanding: selectedOpt.dataset.outstanding,
                        user_id: selectedOpt.dataset.user
                    });
                }
            }

            invoiceSelect.on('select2:select', async (e) => {
                const data = e.params.data;
                setInvoiceContext(data);

                try {
                    const res = await fetch(invoiceJsonUrl(data.id));
                    if (res.ok) {
                        const full = await res.json();
                        setInvoiceContext(Object.assign({}, data, full));
                    }
                } catch (err) {
                    console.warn('Invoice fetch failed', err);
                }
            });

            invoiceSelect.on('select2:clear', () => {
                clearInvoiceContext();
            });

            if (paymentMethod) {
                paymentMethod.addEventListener('change', renderMethodFields);
                renderMethodFields();
            }

            function setInvoiceContext(data) {
                if (!data) return;
                receiptableType.value = 'sale_invoice';
                receiptableId.value = data.id;
                const outstanding = Number(data.outstanding || 0);
                if (outstandingBadge) {
                    outstandingBadge.textContent = outstanding ? `Outstanding ${poundSymbol}${outstanding.toFixed(2)}` : '';
                    outstandingBadge.classList.toggle('d-none', !outstanding);
                }
                if (amountInput) {
                    amountInput.max = outstanding > 0 ? outstanding : '';
                    if (!amountInput.value || Number(amountInput.value) === 0) {
                        amountInput.value = outstanding > 0 ? outstanding.toFixed(2) : '';
                    }
                }
                if (userSelect && data.user_id) {
                    const found = [...userSelect.options].find(o => String(o.value) === String(data.user_id));
                    if (found) {
                        userSelect.value = data.user_id;
                    }
                }
            }

            function clearInvoiceContext() {
                receiptableType.value = '';
                receiptableId.value = '';
                if (amountInput) amountInput.removeAttribute('max');
                if (outstandingBadge) {
                    outstandingBadge.textContent = '';
                    outstandingBadge.classList.add('d-none');
                }
            }

            function enforceSelectDefaults() {
                if (currentSelections.user && userSelect) {
                    userSelect.value = currentSelections.user;
                }
                const bank = document.querySelector('select[name="sys_bank_account_id"]');
                if (bank && currentSelections.bank) {
                    bank.value = currentSelections.bank;
                }
                if (paymentMethod && currentSelections.method) {
                    paymentMethod.value = currentSelections.method;
                }
                renderMethodFields();
            }

            form.addEventListener('submit', (e) => {
                const outstanding = parseFloat(amountInput.max || '0');
                const amount = parseFloat(amountInput.value || '0');
                if (receiptableId.value && outstanding && amount > outstanding + 0.0001) {
                    e.preventDefault();
                    alert(`Amount cannot exceed outstanding (${poundSymbol}${outstanding.toFixed(2)}).`);
                    amountInput.focus();
                    return;
                }
            });

            function renderMethodFields() {
                if (!methodFields || !methodDetails) return;
                methodFields.innerHTML = '';
                const selected = paymentMethod.value;
                if (!selected) {
                    methodDetails.style.display = 'none';
                    return;
                }
                const code = (methodMap[selected]?.code || methodMap[selected]?.name || '').toLowerCase();
                const meta = metaInitial || {};

                const addInput = (name, label, type = 'text', extra = {}) => {
                    const value = meta[name] ?? '';
                    const col = document.createElement('div');
                    col.className = 'col-md-6';
                    const inputId = `payment_meta_${name}`;
                    col.innerHTML = `
                        <label class="form-label" for="${inputId}">${label}${extra.required ? ' <span class="text-danger">*</span>' : ''}</label>
                        <input type="${type}" class="form-control" id="${inputId}" name="payment_meta[${name}]" value="${value ?? ''}"
                            ${extra.required ? 'required' : ''} ${extra.max ? `maxlength="${extra.max}"` : ''}>
                    `;
                    methodFields.appendChild(col);
                };

                if (code.includes('bank')) {
                    addInput('txn_ref', 'Transaction / UTR reference', 'text', { required: true, max: 100 });
                    addInput('bank_name', 'Paying bank name', 'text', { required: false, max: 100 });
                } else if (code.includes('cheque') || code.includes('check')) {
                    addInput('cheque_no', 'Cheque number', 'text', { required: true, max: 50 });
                    addInput('cheque_date', 'Cheque date', 'date', { required: false });
                    addInput('bank_name', 'Bank name', 'text', { required: false, max: 100 });
                } else if (code.includes('card') || code.includes('credit')) {
                    addInput('last4', 'Card last 4', 'text', { required: true, max: 4 });
                    addInput('auth_code', 'Auth code / transaction ID', 'text', { required: true, max: 20 });
                    addInput('brand', 'Card brand', 'text', { required: false, max: 20 });
                } else {
                    addInput('reference', 'Payment reference', 'text', { required: false, max: 100 });
                }
                methodDetails.style.display = methodFields.children.length ? 'block' : 'none';
            }
        })();
    </script>
@endpush
