@extends('backend.layout.app')

@section('content')
    <h1>Create Offer</h1>
    <form action="{{ route('admin.offers.store') }}" method="POST">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property_id }}">

        <label for="price">Price:</label>
        <input type="text" name="price" id="price" required>

        <label for="deposit">Deposit:</label>
        <input type="text" name="deposit" id="deposit" required>

        <label for="term">Term:</label>
        <select name="term" id="term" required>
            <option value="6 months">6 months</option>
            <option value="12 months">12 months</option>
            <option value="18 months">18 months</option>
            <option value="24 months">24 months</option>
        </select>

        <label for="move_in_date">Move In Date:</label>
        <input type="date" name="move_in_date" id="move_in_date" required>

        <button type="submit">Create Offer</button>
    </form>
@endsection
