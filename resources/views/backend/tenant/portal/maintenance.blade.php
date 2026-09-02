@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Maintenance</p>
            <h1>Repair requests</h1>
            <p>Status of issues you have reported. Quotes, costs and contractor notes stay with the office.</p>
        </div>
        @if($canRaise)
            <a class="tp-btn" href="{{ route('admin.property_repairs.create') }}">Report an issue</a>
        @endif
    </div>

    <div class="tp-card">
        @if($repairs->isEmpty())
            <p class="tp-empty">No repair requests for your property yet.</p>
        @else
            <table class="tp-table">
                <thead>
                    <tr>
                        <th>Issue</th>
                        <th>Property</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reported</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($repairs as $repair)
                        <tr>
                            <td>{{ $repair->description ? \Illuminate\Support\Str::limit($repair->description, 100) : 'Repair #'.$repair->id }}</td>
                            <td>{{ $repair->property?->full_address ?: ($repair->property?->prop_name ?: 'Property not found') }}</td>
                            <td>{{ $repair->priority ?: '—' }}</td>
                            <td><span class="tp-pill">{{ $repair->status ?: 'Open' }}</span></td>
                            <td>{{ $repair->created_at?->format('d M Y') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
