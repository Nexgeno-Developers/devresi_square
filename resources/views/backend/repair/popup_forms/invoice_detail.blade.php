@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    @if ($repairIssue->invoice)
        <div class="row mt-md-5">
            <div class="col-6">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label d-block"><strong>Invoice To:</strong></label>
                        <p>
                            {{ $repairIssue->workOrder->invoice_to ?? '-' }}
                        </p>
                    </div>
        
                    @php
                        $user = $invoice->user ?? null;
                    @endphp
        
                    @if($repairIssue->workOrder->invoice_to === 'Landlord' || $repairIssue->workOrder->invoice_to === 'Tenant' || $repairIssue->workOrder->invoice_to === 'Company')
                    <div class="col-6">
                        <div class="mb-3">
                            <h6>Bill To</h6>
                            <p><strong>Name:</strong> {{ $user->name ?? '-' }}</p>
                            <p><strong>Address:</strong> {{ $user->address ?? '-' }}</p>
                            <p><strong>Email:</strong> {{ $user->email ?? '-' }}</p>
                            <p><strong>Phone:</strong> {{ $user->phone ?? '-' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        
            <div class="col-6">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label"><strong>Invoice Number:</strong></label>
                        <p>{{ $invoice->invoice_number ?? '-' }}</p>
                    </div>
        
                    <div class="col-6 mb-3">
                        <label class="form-label"><strong>Invoice Date:</strong></label>
                        <p>{{ $invoice->invoice_date ?? '-' }}</p>
                    </div>
        
                    <div class="col-6 mb-3">
                        <label class="form-label"><strong>Due Date:</strong></label>
                        <p>{{ $invoice->due_date ?? '-' }}</p>
                    </div>
        
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><strong>Payment Terms:</strong></label>
                        <p>{{ $repairIssue->workOrder->payment_by ?? '-' }}</p>
                    </div>
        
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><strong>Status:</strong></label>
                        <p>{{ $invoice->status ?? '-' }}</p>
                    </div>
                </div>
            </div>
        
            <div class="row">
                <div class="col mb-3">
                    <label class="form-label"><strong>Charge to Landlord:</strong></label>
                    <p>£{{ number_format($repairIssue->workOrder->charge_to_landlord ?? 0, 2) }}</p>
                </div>
            </div>
        
            <h4>Invoice Items</h4>
            <table class="table">
                <thead class="table-secondary">
                    <tr>
                        <th>Title</th>
                        <th>Detail</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Tax Rate (%)</th>
                        <th>Tax Amount</th>
                        <th>Total (Excl. Tax)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                        $taxTotal = 0;
                    @endphp
        
                    @foreach($invoice->items ?? [] as $item)
                        @php
                            $unitTotal = $item->unit_price * $item->quantity;
                            $taxAmount = ($item->tax_rate / 100) * $unitTotal;
                            $subtotal += $unitTotal;
                            $taxTotal += $taxAmount;
                        @endphp
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->description }}</td>
                            <td>£{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->tax_rate }}</td>
                            <td>£{{ number_format($taxAmount, 2) }}</td>
                            <td>£{{ number_format($unitTotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
                        <td>£{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Tax Total:</strong></td>
                        <td>£{{ number_format($taxTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                        <td>£{{ number_format($subtotal + $taxTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        
            <div class="col-8 mb-3">
                <label class="form-label"><strong>Notes:</strong></label>
                <p>{!! nl2br(e($invoice->notes ?? '-')) !!}</p>
            </div>
        </div>
    @else
        <div class="alert alert-warning my-3">
            No invoice found for this repair issue.
        </div>
    @endif
 
    

@else
    <form id="propertyServiceForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="property_services">

        <div class="form-group">
            <label>Parking</label>
            <div class="rs_radio_btns">
                <label><input type="radio" name="parking" value="0" {{ (isset($property) && $property->parking == '0') ? 'checked' : '' }} required /> No</label>
                <label><input type="radio" name="parking" value="1" {{ (isset($property) && $property->parking == '1') ? 'checked' : '' }} required /> Yes</label>
                @error('parking')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group" id="parking_location_group" style="display: none;">
            <label for="parking_location">Parking Location</label>
            <input type="text" name="parking_location" class="form-control"
                value="{{ $property->parking_location ?? '' }}" />
            @error('parking_location')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Service</label>
            <div class="row">
                <div class="col-lg-6 col-12">
                    <select name="service" class="form-control" required>
                        <option value="" disabled {{ (isset($property) && $property->service == '') ? 'selected' : '' }}>
                            Select a service</option>
                        <option value="Comprehensive Management" {{ (isset($property) && $property->service == 'Comprehensive Management') ? 'selected' : '' }}>Comprehensive Management </option>
                        <option value="Standard Management" {{ (isset($property) && $property->service == 'Standard Management') ? 'selected' : '' }}>Standard Management</option>
                        <option value="fully manged" {{ (isset($property) && $property->service == 'fully manged') ? 'selected' : '' }}>Fully Manged</option>
                        <option value="let and rent collect" {{ (isset($property) && $property->service == 'let and rent collect') ? 'selected' : '' }}>Let And Rent Collect</option>
                        <option value="let only" {{ (isset($property) && $property->service == 'let only') ? 'selected' : '' }}>Let Only</option>
                    </select>
                    @error('service')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @if(isset($property) && $property->property_type == 'lettings' || $property->property_type == 'both')
            <div class="form-group">
                <input type="checkbox" name="pets_allow" id="pets_allow" style="width: 5%;"
                    value="{{ isset($property) && $property->pets_allow == 1 ? 1 : 0 }}" {{ isset($property) && $property->pets_allow == 1 ? 'checked' : '' }} />
                <label for="pets_allow">Pets Allowed</label>
            </div>
        @endif

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>

    <script>
        function initializeParkingRadios() {
            // Declare the variable for radio buttons if it's not already declared
            let parkingRadios = document.querySelectorAll('input[name="parking"]');
            const parkingLocationGroup = document.getElementById('parking_location_group');

            // Add event listener to handle changes
            parkingRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    // Show or hide the parking location group based on the selected value
                    parkingLocationGroup.style.display = (radio.value === '1') ? 'block' : 'none';
                });
            });

            // Check if any radio button is already selected, and show the field accordingly
            const selectedRadio = Array.from(parkingRadios).find(radio => radio.checked);
            if (selectedRadio && selectedRadio.value === '1') {
                parkingLocationGroup.style.display = 'block'; // Show the field if selected value is '1'
            } else {
                parkingLocationGroup.style.display = 'none';  // Hide the field if '0' or none selected
            }
        }

        // Call the function to initialize
        initializeParkingRadios();

    </script>
@endif