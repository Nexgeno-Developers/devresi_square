@if (!isset($editMode) || !$editMode)
    <!-- Display View -->
    <h5 class="fw-bold fs-4 my-3 pt-2">Property Manager Assignments</h5>

    @if($repairIssue->repairIssuePropertyManagers->count())
        @foreach($repairIssue->repairIssuePropertyManagers as $index => $assignment)
            <div class="mb-3 p-3 bg-light rounded border">
                <p class="mb-1 fs-6">
                    <span class="fw-semibold text-primary">#{{ $index + 1 }}</span>
                </p>
                <p class="mb-1 fs-6">
                    <span class="fw-semibold">Manager:</span>
                    {{ $assignment->propertyManager->name ?? 'N/A' }}
                </p>
                <p class="mb-1 fs-6 text-muted">
                    {{ $assignment->propertyManager->email ?? 'N/A' }}
                </p>
                <p class="mb-0 fs-6">
                    <span class="fw-semibold">Assigned At:</span>
                    {{ \Carbon\Carbon::parse($assignment->assigned_at)->format('d M Y, H:i') }}
                </p>
            </div>
        @endforeach
    @else
        <p class="text-muted fs-6">No property manager assignments available.</p>
    @endif

@else
    <form id="availabilityPricingForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="availability_pricing">

        <div class="mb-3">
            <label>Availability</label>
            <input type="date" name="available_from" class="form-control" value="{{ $repairIssue->property->available_from }}">
        </div>
        <div class="form-group">
            <label for="local_authority">Local Authority</label>
            <input type="text" name="local_authority" id="local_authority" class="form-control"
                value="{{ $localAuthority }}">
        </div>

        <div class="form-group">
            <label for="tenure">Tenure</label>
            <select name="tenure" id="tenure" class="form-control">
                <option value="leasehold" {{ isset($repairIssue->property) && $repairIssue->property->tenure == 'leasehold' ? 'selected' : '' }}>
                    Leasehold</option>
                <option value="freehold" {{ isset($repairIssue->property) && $repairIssue->property->tenure == 'freehold' ? 'selected' : '' }}>
                    Freehold
                </option>
                <option value="commonhold"
                    {{ isset($repairIssue->property) && $repairIssue->property->tenure == 'commonhold' ? 'selected' : '' }}>
                    Commonhold</option>
                <option value="feudal" {{ isset($repairIssue->property) && $repairIssue->property->tenure == 'feudal' ? 'selected' : '' }}>
                    Feudal
                </option>
                <option value="share_of_freehold"
                    {{ isset($repairIssue->property) && $repairIssue->property->tenure == 'share_of_freehold' ? 'selected' : '' }}>Share of
                    Freehold</option>
            </select>

        </div>

        @if ($propertyType == 'sales' || $propertyType == 'both')
            <div class="form-group">
                <label for="length_of_lease">Length of Lease (in year)</label>
                <input type="text" name="length_of_lease" id="length_of_lease" class="form-control"
                    value="{{ $lengthOfLease }}">
            </div>

            <div class="form-group">
                <label for="estate_charge">Estate Charge (annual)</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">{{ getPoundSymbol() }}</div>
                    <input type="text" name="estate_charge" id="estate_charge" class="form-control"
                        value="{{ $estateCharge }}">
                </div>
            </div>


            <div class="form-group">
                <label for="ground_rent">Ground Rent (annual)</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">{{ getPoundSymbol() }}</div>
                    <input type="text" name="ground_rent" id="ground_rent" class="form-control"
                        value="{{ $groundRent }}">
                </div>
            </div>

            <div class="form-group">
                <label for="service_charge">Service Charge (annual)</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">{{ getPoundSymbol() }}</div>
                    <input type="text" name="service_charge" id="service_charge" class="form-control"
                        value="{{ $serviceCharge }}">
                </div>
            </div>


            {{-- <div class="form-group">
            <label for="estate_charges[amount]">Estate Charge</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">{{ getPoundSymbol() }}</div>
                <input required type="text" name="estate_charges[amount]" id="estate_charges" class="form-control"
                    value="{{ old('estate_charges.amount', $property->estateCharge->amount ?? '') }}">
            </div>
            @error('estate_charges.amount')
            <div class="text-danger">{{ $message }}</div>
            @enderror
        </div> --}}
        @endif

        <div class="form-group">
            <label for="miscellaneous_charge">Miscellaneous Charge (annual)</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">{{ getPoundSymbol() }}</div>
                <input type="text" name="miscellaneous_charge" id="miscellaneous_charge" class="form-control"
                    value="{{ $miscellaneousCharge }}">
            </div>
        </div>

        <!-- Listing Sale Price Input (Show only if type is sales or both) -->
        @if ($propertyType == 'sales' || $propertyType == 'both')
            <div class="form-group">
                <label for="lprice">Listing Sale Price</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">{{ getPoundSymbol() }}</div>
                    <input type="text" name="price" id="price" class="form-control"
                        value="{{ $salePrice }}">
                </div>
            </div>
        @endif

        <!-- Letting Price Input (Show only if type is letting or both) -->
        @if ($propertyType == 'lettings' || $propertyType == 'both')
            <div class="form-group">
                <label for="letting_price">Letting Price</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">{{ getPoundSymbol() }}</div>
                    <input type="text" name="letting_price" id="letting_price" class="form-control"
                        value="{{ $lettingPrice }}">
                </div>
            </div>
        @endif


        <div class="form-group">
            <label for="annual_council_tax">Annual Council Tax (annual)</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">{{ getPoundSymbol() }}</div>
                <input type="text" name="annual_council_tax" id="annual_council_tax" class="form-control"
                    value="{{ $annualCouncilTax }}">
            </div>
        </div>

        <div class="form-group">
            <label for="council_tax_band">Council Tax Band</label>
            <select name="council_tax_band" id="council_tax_band" class="form-control">
                <option value="" disabled selected>Select a band</option>
                <option value="A" {{ old('council_tax_band', $councilTaxBand) == 'A' ? 'selected' : '' }}>A
                </option>
                <option value="B" {{ old('council_tax_band', $councilTaxBand) == 'B' ? 'selected' : '' }}>B
                </option>
                <option value="C" {{ old('council_tax_band', $councilTaxBand) == 'C' ? 'selected' : '' }}>C
                </option>
                <option value="D" {{ old('council_tax_band', $councilTaxBand) == 'D' ? 'selected' : '' }}>D
                </option>
                <option value="E" {{ old('council_tax_band', $councilTaxBand) == 'E' ? 'selected' : '' }}>E
                </option>
                <option value="F" {{ old('council_tax_band', $councilTaxBand) == 'F' ? 'selected' : '' }}>F
                </option>
                <option value="G" {{ old('council_tax_band', $councilTaxBand) == 'G' ? 'selected' : '' }}>G
                </option>
                <option value="H" {{ old('council_tax_band', $councilTaxBand) == 'H' ? 'selected' : '' }}>H
                </option>
            </select>
        </div>

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>

@endif
