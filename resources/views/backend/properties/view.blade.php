@extends('backend.layout.app')

@section('content')

@php
    $property_name = $property->prop_name;
    $city_name = $property->city;
    $country_name = $property->country;
@endphp

<div class="main-content pt-4">
    <div class="content_header_full d-flex justify-content-between">
        <div class="main_title">
            Property Detail View
        </div>
        <div class="header_btns">
            <a class="btn btn-outline-primary edit_property" href="{{ route('admin.properties.edit', $property->id) }}">Edit Property</a>
            <!-- <a id="quick-add-btn" href="{{ route('admin.properties.create') }}" class="btn btn-outline-primary">Quick Add Property</a> -->
            <a id="quick-add-btn" href="{{ route('admin.properties.quick') }}" class="btn btn-outline-primary">Quick Add Property</a>
            <a href="{{ route('admin.properties.create') }}?stepform" class="btn btn-outline-secondary">Add Property</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="d-flex">
                <h5>Property Name :</h5>
                <span> {{ $property_name }} </span>
            </div>
            <div class="pdc_item">
                <strong>Landlord residing in UK:</strong> Yes
            </div>
        </div>
        <div class="col-md-4">
            <h5>Country Name<span> : </span><span> {{ $country_name }} </span></h5>
            <div class="pdc_item">
                <strong>Landlord residing in UK:</strong> Yes
            </div>
        </div>
        <div class="col-md-4">
            <h5>City Name<span> : </span><span> {{ $city_name }} </span></h5>
            <div class="pdc_item">
                <strong>Landlord residing in UK:</strong> Yes
            </div>
        </div>
    </div>
</div>
@endsection

@section('page.scripts')
<script>

</script>
@endsection
