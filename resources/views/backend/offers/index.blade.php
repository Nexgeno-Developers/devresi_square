@extends('backend.layout.app')

@section('content')
    <h1>Offers</h1>
    <a href="{{ route('admin.offers.create') }}">Create New Offer</a>
    <table>
        <thead>
            <tr>
                <th>Property</th>
                <th>Price</th>
                <th>Deposit</th>
                <th>Term</th>
                <th>Move In Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offers as $offer)
                <tr>
                    <td>{{ $offer->property->name }}</td>
                    <td>{{ $offer->price }}</td>
                    <td>{{ $offer->deposit }}</td>
                    <td>{{ $offer->term }}</td>
                    <td>{{ $offer->move_in_date }}</td>
                    <td>
                        <a href="{{ route('offers.edit', $offer->id) }}">Edit</a>
                        <form action="{{ route('offers.destroy', $offer->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
