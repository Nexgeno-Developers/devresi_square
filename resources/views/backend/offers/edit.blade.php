@extends('backend.layout.app')

@section('content')
@php
    $terms = ['6 months', '12 months', '18 months', '24 months'];
@endphp
<div class="container-fluid py-3" style="max-width: 720px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Edit offer</h2>
        <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary">Back to offers</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.offers.update', $offer->id) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <input type="hidden" name="property_id" value="{{ $offer->property_id }}">

                <div class="col-12 col-sm-6">
                    <label for="price" class="form-label">Price</label>
                    <input type="text" name="price" id="price" class="form-control" value="{{ old('price', $offer->price) }}" required>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="deposit" class="form-label">Deposit</label>
                    <input type="text" name="deposit" id="deposit" class="form-control" value="{{ old('deposit', $offer->deposit) }}" required>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="term" class="form-label">Term</label>
                    <select name="term" id="term" class="form-select" required>
                        @foreach($terms as $term)
                            <option value="{{ $term }}" @selected(old('term', $offer->term) === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label for="move_in_date" class="form-label">Move in date</label>
                    <input type="date" name="move_in_date" id="move_in_date" class="form-control" value="{{ old('move_in_date', $offer->move_in_date) }}" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Update offer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
