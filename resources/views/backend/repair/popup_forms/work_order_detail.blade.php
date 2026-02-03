@php
    $propertyId = $repairIssue->property_id;
    $quoteAttachment = $repairIssue->workOrder->quote_attachment ?? null;
@endphp
@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    @if ($repairIssue->workOrder)
        <div class="row">
            <div class="col-6">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h5>Work Order #{{ $repairIssue->workOrder->works_order_no }} Details</h5>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Assign Status</label>
                        <p>{{ $repairIssue->workOrder->job_status ?? '-' }}</p>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Job Type</label>
                        <p>{{ $repairIssue->workOrder->jobType->name ?? '-' }}</p>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Job Sub Type</label>
                        <p>{{ $repairIssue->workOrder->jobSubType->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tentative Start Date</label>
                        <p>{{ $repairIssue->workOrder->tentative_start_date ?? '-' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tentative End Date</label>
                        <p>{{ $repairIssue->workOrder->tentative_end_date ?? '-' }}</p>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Booked Date</label>
                        <p>{{ $repairIssue->workOrder->booked_date ?? '-' }}</p>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Work Order Status</label>
                        <p>{{ $repairIssue->workOrder->status ?? '-' }}</p>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Charge To</label>
                        <p>{{ $repairIssue->workOrder->invoice_to ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <h4 class="mt-4">Job Scope</h4>

        <table class="table table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th>Job Title</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Tax Type</th>
                    <th>Tax Rate (%)</th>
                    <th>Tax Amount</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($repairIssue->workOrder->items ?? [] as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->description }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->taxRate->name ?? '-' }}</td>
                        <td>{{ $item->tax_rate ?? 0 }}</td>
                        <td>${{ number_format(($item->unit_price * $item->quantity * $item->tax_rate) / 100, 2) }}</td>
                        <td>${{ number_format(($item->unit_price * $item->quantity) + (($item->unit_price * $item->quantity * $item->tax_rate) / 100), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $subtotal = $repairIssue->workOrder->items->sum(fn($item) => $item->unit_price * $item->quantity);
                    $taxTotal = $repairIssue->workOrder->items->sum(fn($item) => ($item->unit_price * $item->quantity * $item->tax_rate) / 100);
                    $grandTotal = $subtotal + $taxTotal;
                @endphp
                <tr>
                    <td colspan="7" class="text-end fw-bold">Subtotal:</td>
                    <td>${{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="text-end fw-bold">Tax Total:</td>
                    <td>${{ number_format($taxTotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="text-end fw-bold">Grand Total:</td>
                    <td>${{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($quoteAttachment)
            <div class="mb-3">
                <label class="form-label fw-bold">Quote Attachment</label><br>
                <x-attachment-viewer file-url="{{ uploaded_asset($quoteAttachment) }}" title="View Quote Attachment"
                    button-class="btn btn_secondary" icon-class="fa-solid fa-eye" modal-size="modal-xl" modal-scrollable="false"
                    background-color="#f8f9fa" border-radius="12px" close-button-class="btn-close-dark" downloadable="true" />
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label fw-bold">Notes</label>
            <p>{{ $repairIssue->workOrder->extra_notes ?? '-' }}</p>
        </div>
    @else
        <div class="alert alert-warning">
            No work order found for this repair issue.
        </div>
    @endif

@else
    <form id="propertyStatusForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="property_status">

        @if(isset($property) && ($property->property_type == 'sales' || $property->property_type == 'both'))
            <div class="form-group">
                <label for="sales_current_status">Sales Status</label>
                <select name="sales_current_status" id="sales_current_status" class="form-control" required>
                    <option value="" disabled {{ (isset($property) && $property->sales_current_status == '') ? 'selected' : ''  }}>
                        Select a Status</option>
                    <option value="for sale" {{ (isset($property) && $property->sales_current_status == 'for sale') ? 'selected' : '' }}>For Sale</option>
                    <option value="on hold" {{ (isset($property) && $property->sales_current_status == 'on hold') ? 'selected' : '' }}>On Hold</option>
                    <option value="under offer" {{ (isset($property) && $property->sales_current_status == 'under offer') ? 'selected' : '' }}>Under Offer</option>
                    <option value="sold" {{ (isset($property) && $property->sales_current_status == 'sold') ? 'selected' : '' }}>
                        Sold</option>
                    <option value="sold STC" {{ (isset($property) && $property->sales_current_status == 'sold STC') ? 'selected' : '' }}>Sold STC</option>
                    <option value="sold by other" {{ (isset($property) && $property->sales_current_status == 'sold by other') ? 'selected' : '' }}>Sold By Other</option>
                    <option value="exchanged" {{ (isset($property) && $property->sales_current_status == 'exchanged') ? 'selected' : '' }}>Exchanged</option>
                    <option value="available" {{ (isset($property) && $property->sales_current_status == 'available') ? 'selected' : '' }}>Available</option>
                    <option value="let agreed" {{ (isset($property) && $property->sales_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                </select>
                @error('sales_current_status')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if(isset($property) && ($property->property_type == 'lettings' || $property->property_type == 'both'))
            <div class="form-group">
                <label for="letting_current_status">Letting Status</label>
                <select name="letting_current_status" id="letting_current_status" class="form-control" required>
                    <option value="" disabled {{ (isset($property) && $property->letting_current_status == '') ? 'selected' : ''  }}>Select a Status</option>
                    <option value="not available" {{ (isset($property) && $property->letting_current_status == 'not available') ? 'selected' : '' }}>Not Available</option>
                    <option value="available" {{ (isset($property) && $property->letting_current_status == 'available') ? 'selected' : '' }}>Available</option>
                    <option value="let agreed" {{ (isset($property) && $property->letting_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                    <option value="let by other" {{ (isset($property) && $property->letting_current_status == 'let by other') ? 'selected' : '' }}>Let By Other</option>
                </select>
                @error('letting_current_status')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="form-group">
            <label for="status_description">Description</label>
            <textarea name="status_description" id="status_description" rows="6"
                class="form-control">{{ isset($property) && $property->status_description ? $property->status_description : '' }}</textarea>
            <div class="input_tag">0/5000 words</div>
            @error('status_description')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif