<form action="{{ route('admin.invoices.update', $invoice->id ?? '') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row mt-md-5">
        <div class="col-6">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-group">
                        <label class="form-label d-block">Invoice To</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="invoice_to" value="Landlord"
                                id="invoice_landlord" required {{ old('invoice_to', $repairIssue->workOrder->invoice_to ?? '') == 'Landlord' ? 'checked' : '' }}>
                            <label class="form-check-label" for="invoice_landlord">Landlord</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="invoice_to" value="Tenant"
                                id="invoice_tenant" {{ old('invoice_to', $repairIssue->workOrder->invoice_to ?? '') == 'Tenant' ? 'checked' : '' }}>
                            <label class="form-check-label" for="invoice_tenant">Tenant</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="invoice_to" value="Company"
                                id="invoice_company" {{ old('invoice_to', $repairIssue->workOrder->invoice_to ?? '') == 'Company' ? 'checked' : '' }}>
                            <label class="form-check-label" for="invoice_company">
                                Company
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <!-- Dynamic Dropdown Container -->
                    <div id="invoiceToContainer" class="mb-3"></div>
                </div>
                <div class="col-6">
                    <!-- Hidden Fields -->
                    <input type="hidden" id="existingInvoiceToId"
                        value="{{ old('user_id', $invoice->user_id ?? '') }}">

                    <!-- User Details (Hidden Initially) -->
                    <div id="userDetails" class="mt-3" style="display: none;">
                        <h6>Bill To</h6>
                        <p><strong>Name:</strong> <span id="userName"></span></p>
                        <p><strong>Address:</strong> <span id="userAddress"></span></p>
                        <p><strong>Email:</strong> <span id="userEmail"></span></p>
                        <p><strong>Phone:</strong> <span id="userPhone"></span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="row">
                <!-- Invoice Number -->
                <div class="col-12 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            Invoice Number
                            <small class="text-body-secondary small"> (Number will auto generate) </small>
                        </label>

                        <input type="text" name="invoice_number" class="form-control"
                            value="{{ old('invoice_number', $invoice->invoice_number ?? '') }}" readonly required>
                    </div>
                </div>

                <!-- Invoice Date -->
                <div class="col-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control"
                            value="{{ old('invoice_date', $invoice->invoice_date ?? '') }}" required>
                    </div>
                </div>

                <!-- Due Date -->
                <div class="col-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                            value="{{ old('due_date', $invoice->due_date ?? '') }}" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Payment Terms</label>
                        <select required name="payment_by" class="form-control">
                            <option value="Landlord" {{ old('payment_by', $repairIssue->workOrder->payment_by ?? '') == 'Landlord' ? 'selected' : '' }}>Landlord</option>
                            <option value="Tenant" {{ old('payment_by', $repairIssue->workOrder->payment_by ?? '') == 'Tenant' ? 'selected' : '' }}>Tenant</option>
                            <option value="Company" {{ old('payment_by', $repairIssue->workOrder->payment_by ?? '') == 'Company' ? 'selected' : '' }}>Company</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue
                            </option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col mb-3">
                <div class="form-group">
                    <label class="form-label">Charge to Landlord</label>
                    <input type="number" step="0.01" name="charge_to_landlord" class="form-control"
                        value="{{ old('charge_to_landlord', $repairIssue->workOrder->charge_to_landlord ?? '') }}">
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <h4>Invoice Items</h4>
        <table class="table">
            <thead class="table-secondary">
                <tr>
                    <th>Title</th>
                    <th>Detail</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    {{-- <th>Tax Type</th> --}}
                    <th>Tax Rate (%)</th>
                    <th>Tax Amount</th>
                    <th>Total (Excl. Tax)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="invoice-items">
                @if (!empty($invoice->items))
                    @foreach($invoice->items as $index => $item)
                        <tr>
                            <td><input type="text" name="items[{{ $index }}][title]" class="form-control"
                                    value="{{ $item->title }}" required></td>
                            <td><input type="text" name="items[{{ $index }}][description]" class="form-control"
                                    value="{{ $item->description }}" required></td>
                            <td><input type="number" name="items[{{ $index }}][unit_price]"
                                    class="form-control unit-price_invoice" value="{{ $item->unit_price }}" required></td>
                            <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity_invoice"
                                    value="{{ $item->quantity }}" required></td>
                            {{-- <td>
                                <select name="items[{{ $index }}][tax_name]" class="form-control tax-name_invoice">
                                    @foreach($taxRates as $taxRate)
                                    <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}" {{ (!empty($item->
                                        tax_rate_id) && $item->tax_rate_id == $taxRate->id) ? 'selected' :
                                        (empty($item->tax_rate_id) && $taxRate->rate == 0 ? 'selected' : '') }}>
                                        {{ $taxRate->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td> --}}
                            <td><input type="number" name="items[{{ $index }}][tax_rate]" class="form-control tax-rate_invoice"
                                    value="{{ $item->tax_rate ?? '' }}" required></td>
                            <td><input type="text" class="form-control tax-amount_invoice" readonly></td>
                            <td><input type="text" class="form-control total-price_invoice" readonly></td>
                            <td>
                                @if ($loop->last)
                                    <button type="button" class="btn btn_secondary add-invoice-item"><i
                                            class="fa-solid fa-plus"></i></button>
                                @else
                                    <button type="button" class="btn btn-danger remove-invoice-item"><i
                                            class="fa-solid fa-minus"></i></button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="border-0 text-end"><strong>Subtotal:</strong></td>
                    <td class="border border-0"><input type="text" id="subtotal_invoice" class="form-control" readonly>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="border-warning text-end"><strong>Tax Total:</strong></td>
                    <td class="border-warning"><input type="text" id="tax_total_invoice" class="form-control" readonly>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" class="border-0 text-end"><strong>Grand Total:</strong></td>
                    <td class="border border-0"><input type="text" id="grand-total_invoice"
                            class="border-warning form-control" readonly></td>
                </tr>
            </tfoot>
        </table>

        <!-- Notes -->
        <div class="col-8 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $invoice->notes ?? '') }}</textarea>
        </div>

        <!-- Submit Button -->
        <div class="submit-btn mt-2 mt-md-4">
            <button type="submit" class="float-end btn btn_secondary">Update Invoice</button>
        </div>
    </div>
</form>