<h1 class="mb-4">Additional Property Services</h1>

<div class="row">
    <!-- First Column -->
    <div class="col-md-6">

        <h2 class="title my-3">Marketing Details</h5>
        <ul class="list-unstyled">
            <li><strong>Listing Sales Price:</strong> {{ getPoundSymbol() . $property->price ?? '' }}</li>
            <li><strong>Ground Rent:</strong> {{ getPoundSymbol() . $property->ground_rent ?? '' }}</li>
            <li><strong>Service Charge:</strong> {{ getPoundSymbol() . $property->service_charge ?? '' }}</li>
            <li><strong>Estate Charge:</strong> {{ getPoundSymbol() . $property->estate_charge ?? '' }}</li>
            <li><strong>Area Square Feet:</strong> {{ isset($property->square_feet) ? number_format($property->square_feet, 2) : '' }}</li>
            <li><strong>Area Square Meter:</strong> {{ isset($property->square_meter) ? number_format($property->square_meter, 2) : '' }}</li>            
            <li><strong>Parking:</strong> {{ $property->parking == 1 ? 'Yes' : 'No' }}</li>
            @if ($property->parking == 1)
                <li><strong>Parking Location:</strong> {{ $property->parking_location ?? '' }}</li>
            @endif
        </ul>

    </div>

    <!-- Second Column -->
    <div class="col-md-6">
        <ul class="list-unstyled">
            <li><strong>Annual Council Tax:</strong> {{ $property->annual_council_tax ?? '' }}</li>
            <li><strong>Council Tax Band:</strong> {{ $property->council_tax_band ?? '' }}</li>
            <li><strong>Tenure:</strong> {{ $property->tenure ?? '' }}</li>
            <li><strong>Length of Lease (In Years):</strong> {{ $property->length_of_lease ?? '' }}</li>
            <li><strong>Listing Price Prefix:</strong> For Sale</li>
            <li><strong>Mortgage Provider:</strong> {{ $property->mortgage_provider ?? 'N/A' }}</li>
        </ul>

    </div>
</div>
