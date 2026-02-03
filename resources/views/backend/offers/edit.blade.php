@extends('backend.layout.app')

@section('content')
    <h1>Edit Offer</h1>
    <form action="{{ route('admin.offers.update', $offer->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="property_id" value="{{ $offer->property_id }}">

        <label for="price">Price:</label>
        <input type="text" name="price" id="price" value="{{ $offer->price }}" required>

        <label for="deposit">Deposit:</label>
        <input type="text" name="deposit" id="deposit" value="{{ $offer->deposit }}" required>

        <label for="term">Term:</label>
        <select name="term" id="term" required>
            <option value="6 months" {{ $offer->term == '6 months' ? 'selected' : '' }}>6 months</option>
            <option value="12 months" {{ $offer->term == '12 months' ? 'selected' : '' }}>12 months</option>
            <option value="18 months" {{ $offer->term == '18 months' ? 'selected' : '' }}>18 months</option>
            <option value="24 months" {{ $offer->term == '24 months' ? 'selected' : '' }}>24 months</option>
        </select>

        <label for="move_in_date">Move In Date:</label>
        <input type="date" name="move_in_date" id="move_in_date" value="{{ $offer->move_in_date }}" required>

        <button type="submit">Update Offer</button>
    </form>
@endsection
