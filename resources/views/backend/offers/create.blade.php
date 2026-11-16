@extends('backend.layout.app')

@section('content')
@php
    $selectedPropertyId = old('property_id', request('property_id'));
    $terms = ['6 months', '12 months', '18 months', '24 months'];
@endphp
<div class="container-fluid py-3" style="max-width: 720px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Create offer</h2>
        <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary">Back to offers</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.offers.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-12">
                    <label for="property_id" class="form-label">Property</label>
                    <select name="property_id" id="property_id" class="form-select" required>
                        <option value="">Select a property</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" @selected((string) $selectedPropertyId === (string) $property->id)>{{ $property->name ?: $property->line_1 }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="price" class="form-label">Price</label>
                    <input type="text" name="price" id="price" class="form-control" value="{{ old('price') }}" required>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="deposit" class="form-label">Deposit</label>
                    <input type="text" name="deposit" id="deposit" class="form-control" value="{{ old('deposit') }}" required>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="term" class="form-label">Term</label>
                    <select name="term" id="term" class="form-select" required>
                        @foreach($terms as $term)
                            <option value="{{ $term }}" @selected(old('term') === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="move_in_date" class="form-label">Move in date</label>
                    <input type="date" name="move_in_date" id="move_in_date" class="form-control" value="{{ old('move_in_date') }}" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Create offer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
