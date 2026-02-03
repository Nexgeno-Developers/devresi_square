<div class="modal fade" id="workOrderModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="workOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workOrderModalLabel">Work Order Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="workOrderForm" action="{{ route('admin.work_orders.store') }}" method="POST">
                    @csrf
                    <input type="hidden" id="repair_issue_id" name="repair_issue_id" value="{{ $repairIssue->id }}">
                    <input type="hidden" id="work_order_id" name="work_order_id" value="{{ $repairIssue->workOrder->id ?? '' }}">

                    <div class="row">
                        <h6 class="display-6">Job Details</h6> --}}
                        {{-- <hr>
                        <div class="col-md-12 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Property Address</label>
                                {{-- <p class="set-property-address"></p> --}}
                                
                                <p class="set-property-address">
                                    {{ get_property_address_by_id($propertyId) }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Job Status</label>
                                <select required name="job_status" class="form-control">
                                    <option value="Quote" {{ old('job_status', $repairIssue->workOrder->job_status ?? '') == 'Quote' ? 'selected' : '' }}>Quote</option>
                                    <option value="Awarded" {{ old('job_status', $repairIssue->workOrder->job_status ?? '') == 'Awarded' ? 'selected' : '' }}>Awarded</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Job Type</label>
                                <select required name="job_type_id" id="jobTypeSelect" class="form-control">
                                    <option value="">Select Job Type</option>
                                    @foreach ($jobTypes as $type)
                                        <option value="{{ $type->id }}" 
                                            {{ old('job_type_id', $repairIssue->workOrder->job_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Job Sub Type</label>
                                <select name="job_sub_type_id" id="jobSubTypeSelect" class="form-control">
                                    <option disabled aria-disabled="true" value="">Select Job Sub Type</option>
                                </select>
                            </div>
                        </div>
                        

                        <div class="col-md-12 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Job Scope</label>
                                <textarea required name="job_scope" class="form-control">{{ old('job_scope', $repairIssue->workOrder->job_scope ?? $repairIssue->description ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Tentative Initiation Date of Job</label>
                                <input required type="date" name="tentative_start_date" class="form-control" value="{{ old('tentative_start_date', $repairIssue->workOrder->tentative_start_date ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Tentative Completion Date of Job</label>
                                <input type="date" name="tentative_end_date" class="form-control" value="{{ old('tentative_end_date', $repairIssue->workOrder->tentative_end_date ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Booked Date</label>
                                <input type="date" name="booked_date" class="form-control" value="{{ old('booked_date', $repairIssue->workOrder->booked_date ?? '') }}">
                            </div>
                        </div>
                        <!-- Status Dropdown (Dynamically Updated) -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Status</label>
                                <select required name="status" id="statusSelect" class="form-control">
                                    <!-- Status options will be dynamically inserted here -->
                                </select>
                            </div>
                        </div>

                        <!-- Hidden Field to Store the Preselected Status -->
                        <input type="hidden" id="existingStatus" value="{{ old('status', $repairIssue->workOrder->status ?? '') }}">

                        <div class="col-md-12 mb-3">
                            <div class="form-group"> 
                                <label class="form-label">Notes</label>
                                <textarea required name="extra_notes" class="form-control">{{ old('extra_notes', $repairIssue->workOrder->extra_notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Work Order Items -->
                    <h4>Work Order Items</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Description</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Tax Type</th>
                                <th>Tax Rate (%)</th>
                                <th>Tax Amount</th>
                                <th>Total Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="workorder-items">
                            @if($workorder->items->isEmpty())
                                <tr>
                                    <td><input type="text" name="items[0][title]" class="form-control" required></td>
                                    <td><input type="text" name="items[0][description]" class="form-control" required></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control unit-price" required></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control quantity" required></td>
                                    <td>
                                        <select name="items[0][tax_name]" class="form-control tax-name">
                                            @foreach($taxRates as $taxRate)
                                                <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}">{{ $taxRate->name }}</option>
                                            @endforeach
                                        </select>
                                    </td> 
                                    <td><input type="number" name="items[0][tax_rate]" class="form-control tax-rate" required></td>             
                                    <td><input type="text" class="form-control tax-amount" readonly></td>                   
                                    <td><input type="text" class="form-control total-price" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn_secondary add-item">Add More</button>
                                    </td>
                                </tr>
                            @else
                                @foreach($workorder->items as $index => $item)
                                <tr>
                                    <td><input type="text" name="items[{{ $index }}][title]" class="form-control" value="{{ $item->title }}" required></td>
                                    <td><input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item->description }}" required></td>
                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit-price" value="{{ $item->unit_price }}" required></td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity" value="{{ $item->quantity }}" required></td>
                                    <td>
                                        <select name="items[{{ $index }}][tax_name]" class="form-control tax-name">
                                            @foreach($taxRates as $taxRate)
                                                <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}"
                                                    {{ $item->tax_name == $taxRate->name ? 'selected' : '' }}>
                                                    {{ $taxRate->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td> 
                                    <td><input type="number" name="items[{{ $index }}][tax_rate]" class="form-control tax-rate" value="{{ $item->tax_rate }}" required></td>             
                                    <td><input type="text" class="form-control tax-amount" readonly></td>                   
                                    <td><input type="text" class="form-control total-price" readonly></td>
                                    <td>
                                        @if($loop->last)
                                            <button type="button" class="btn btn_secondary add-item">Add More</button>
                                        @else
                                            <button type="button" class="btn btn-danger remove-item">Remove</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>                        
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td><input type="text" id="subtotal" class="form-control" readonly></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Tax Total:</strong></td>
                                <td><input type="text" id="tax-total" class="form-control" readonly></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                <td><input type="text" id="grand-total" class="form-control" readonly></td>
                            </tr>
                        </tfoot>
                    </table>

                    <button type="button" class="btn btn_secondary" id="add-item">Add Item</button>

                    <div class="modal-footer px-0">
                        <div class="row">
                            <div class="col-auto">
                                <button type="submit" class="btn btn_secondary">Save Work Order</button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn_outline_secondary" id="generateInvoiceBtn">Generate Invoice</button>
                            </div>
                            <div class="col-auto px-0">
                                <button type="button" class="btn btn_outline_secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>
