@extends('backend.layout.app')

@section('content')
<div class="container py-4">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="bi bi-receipt"></i> Edit Invoice #{{ $invoice->invoice_number }}
            </h4>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Invoice Info -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control" 
                               value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control"
                               value="{{ old('invoice_date', $invoice->invoice_date) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                               value="{{ old('due_date', $invoice->due_date) }}" required>
                    </div>
                </div>

                <!-- Client -->
                <div class="mt-4">
                    <label class="form-label">Client</label>
                    <select name="user_id" class="form-select">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $invoice->user_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Invoice Items -->
                <div class="mt-5">
                    <h5><i class="bi bi-list-ul"></i> Invoice Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th>Tax Type</th>
                                    <th class="text-center">Tax Rate (%)</th>
                                    <th class="text-end">Tax Amount</th>
                                    <th class="text-end">Total (Excl. Tax)</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items">
                                @foreach($invoice->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][description]" class="form-control" 
                                               value="{{ $item->description }}" placeholder="Item description" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" 
                                               class="form-control text-end unit-price" value="{{ $item->unit_price }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][quantity]" 
                                               class="form-control text-center quantity" value="{{ $item->quantity }}" required>
                                    </td>
                                    <td>
                                        <select name="items[{{ $index }}][tax_name]" class="form-select tax-name">
                                            @foreach($taxRates as $taxRate)
                                                <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}"
                                                    {{ (!empty($item->tax_name) && $item->tax_name == $taxRate->name) ? 'selected' : 
                                                       (empty($item->tax_name) && $taxRate->rate == 0 ? 'selected' : '') }}>
                                                    {{ $taxRate->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[{{ $index }}][tax_rate]" 
                                               class="form-control text-center tax-rate" value="{{ $item->tax_rate }}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control text-end tax-amount" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control text-end total-price" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><input type="text" id="subtotal" class="form-control text-end" readonly></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Tax Total:</strong></td>
                                    <td><input type="text" id="tax-total" class="form-control text-end" readonly></td>
                                    <td></td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="text" id="grand-total" class="form-control text-end fw-bold" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="add-item">
                        <i class="bi bi-plus-lg"></i> Add Item
                    </button>
                </div>

                <!-- Notes -->
                <div class="mt-4">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Additional information (optional)">{{ old('notes', $invoice->notes) }}</textarea>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@section('page.scripts')
<script>
    $(document).ready(function () {
        function calculateTotals() {
            let subtotal = 0;
            let taxTotal = 0;

            $('#invoice-items tr').each(function () {
                let row = $(this);
                let unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
                let quantity = parseInt(row.find('.quantity').val()) || 1;
                let taxRate = parseFloat(row.find('.tax-rate').val()) || 0;

                let itemTotal = unitPrice * quantity;
                let taxAmount = (itemTotal * taxRate) / 100;

                row.find('.tax-amount').val(taxAmount.toFixed(2));
                row.find('.total-price').val(itemTotal.toFixed(2));

                subtotal += itemTotal;
                taxTotal += taxAmount;
            });

            let grandTotal = subtotal + taxTotal;

            $('#subtotal').val(subtotal.toFixed(2));
            $('#tax-total').val(taxTotal.toFixed(2));
            $('#grand-total').val(grandTotal.toFixed(2));
        }

        $(document).on('input change', '.unit-price, .quantity, .tax-rate', function () {
            calculateTotals();
        });

        $(document).on('change', '.tax-name', function () {
            let row = $(this).closest('tr');
            let selectedTaxRate = $(this).find(':selected').data('rate');
            row.find('.tax-rate').val(selectedTaxRate);
            calculateTotals();
        });

        $(document).on('click', '#add-item', function () {
            let index = $('#invoice-items tr').length;
            let taxOptions = `{!! $taxRates->map(fn($rate) => "<option value='$rate->rate' data-rate='{$rate->rate}' " . ($rate->rate == 0 ? 'selected' : '') . ">$rate->name</option>")->join('') !!}`;

            let newRow = `
                <tr>
                    <td><input type="text" name="items[${index}][description]" class="form-control" placeholder="Item description" required></td>
                    <td><input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control text-end unit-price" required></td>
                    <td><input type="number" name="items[${index}][quantity]" class="form-control text-center quantity" required></td>
                    <td>
                        <select name="items[${index}][tax_name]" class="form-select tax-name">
                            ${taxOptions}
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[${index}][tax_rate]" class="form-control text-center tax-rate" value="0" required></td>
                    <td><input type="text" class="form-control text-end tax-amount" readonly></td>
                    <td><input type="text" class="form-control text-end total-price" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
            $('#invoice-items').append(newRow);
        });

        $(document).on('click', '.remove-item', function () {
            $(this).closest('tr').remove();
            calculateTotals();
        });

        calculateTotals();
    });
</script>
@endsection
