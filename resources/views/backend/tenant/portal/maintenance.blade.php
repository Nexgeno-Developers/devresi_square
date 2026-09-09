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
        @if($canRaise && $tenancies->isNotEmpty())
            <form class="tp-card mb-3" method="POST" action="{{ route('tenant.maintenance.store') }}">
                @csrf
                <p class="tp-metric-label mb-2">Report an issue</p>
                @if($tenancies->count() > 1)
                    <label class="d-block mb-2">
                        Property
                        <select name="property_id" class="form-control">
                            @foreach($tenancies as $tenancy)
                                @if($tenancy->property)
                                    <option value="{{ $tenancy->property_id }}">{{ $tenancy->property->full_address ?: $tenancy->property->prop_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                @else
                    <input type="hidden" name="property_id" value="{{ $tenancies->first()?->property_id }}">
                @endif
                <label class="d-block mb-2">
                    What needs attention?
                    <textarea name="description" class="form-control" rows="3" required maxlength="2000" placeholder="Leaking tap in the kitchen…"></textarea>
                </label>
                <label class="d-block mb-3">
                    Priority
                    <select name="priority" class="form-control">
                        <option value="medium">Normal</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Urgent</option>
                    </select>
                </label>
                <button type="submit" class="tp-btn">Send request</button>
            </form>
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
