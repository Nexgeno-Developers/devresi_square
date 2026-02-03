{{-- @extends('backend.layout.app')

@section('content') --}}
<div class="container">
    <h2>Edit Invoice #{{ $invoice->invoice_number }}</h2>

    <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Invoice Number -->
        <div class="mb-3">
            <label class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
        </div>

        <!-- Invoice Date -->
        <div class="mb-3">
            <label class="form-label">Invoice Date</label>
            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date) }}" required>
        </div>

        <!-- Due Date -->
        <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date) }}" required>
        </div>

        <!-- User (Client) -->
        <div class="mb-3">
            <label class="form-label">Client</label>
            <select name="user_id" class="form-control">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $invoice->user_id == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Invoice Items -->
        <h4>Invoice Items</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Tax Type</th>
                    <th>Tax Rate (%)</th>
                    <th>Tax Amount</th>
                    <th>Total (Excl. Tax)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="invoice-items">
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td><input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item->description }}" required></td>
                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit-price" value="{{ $item->unit_price }}" required></td>
                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity" value="{{ $item->quantity }}" required></td>
                    <td>
                        <select name="items[{{ $index }}][tax_name]" class="form-control tax-name">
                            @foreach($taxRates as $taxRate)
                                <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}"
                                    {{ (!empty($item->tax_name) && $item->tax_name == $taxRate->name) ? 'selected' : 
                                       (empty($item->tax_name) && $taxRate->rate == 0 ? 'selected' : '') }}> 
                                    {{ $taxRate->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>                      
                    <td><input type="number" name="items[{{ $index }}][tax_rate]" class="form-control tax-rate" value="{{ $item->tax_rate }}" required></td>             
                    <td><input type="text" class="form-control tax-amount" readonly></td>
                    <td><input type="text" class="form-control total-price" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-item">Remove</button></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
                    <td><input type="text" id="subtotal" class="form-control" readonly></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end"><strong>Tax Total:</strong></td>
                    <td><input type="text" id="tax-total" class="form-control" readonly></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                    <td><input type="text" id="grand-total" class="form-control" readonly></td>
                </tr>
            </tfoot>
        </table>
        

        <button type="button" class="btn btn-primary" id="add-item">Add Item</button>

        <!-- Notes -->
        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $invoice->notes) }}</textarea>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn_secondary">Update Invoice</button>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

{{-- @endsection --}}

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
                    <td><input type="text" name="items[${index}][description]" class="form-control" required></td>
                    <td><input type="number" name="items[${index}][unit_price]" class="form-control unit-price" required></td>
                    <td><input type="number" name="items[${index}][quantity]" class="form-control quantity" required></td>
                    <td>
                        <select name="items[${index}][tax_name]" class="form-control tax-name">
                            ${taxOptions}
                        </select>
                    </td>
                    <td><input type="number" name="items[${index}][tax_rate]" class="form-control tax-rate" value="0" required></td>
                    <td><input type="text" class="form-control tax-amount" readonly></td>
                    <td><input type="text" class="form-control total-price" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-item">Remove</button></td>
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
