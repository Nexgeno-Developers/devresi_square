@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Offers</h2>
        <a href="{{ route('admin.offers.create') }}" class="btn btn-primary">Create new offer</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="rs-stack-table table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Price</th>
                        <th>Deposit</th>
                        <th>Term</th>
                        <th>Move in date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offers as $offer)
                        <tr>
                            <td class="rs-stack-title" data-label="Property">{{ $offer->property?->name }}</td>
                            <td data-label="Price">{{ $offer->price }}</td>
                            <td data-label="Deposit">{{ $offer->deposit }}</td>
                            <td data-label="Term">{{ $offer->term }}</td>
                            <td data-label="Move in date">{{ $offer->move_in_date }}</td>
                            <td class="rs-stack-actions text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.offers.edit', $offer->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('admin.offers.destroy', $offer->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No offers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
